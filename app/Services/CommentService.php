<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\Comment\ApproveCommentDTO;
use App\Data\Comment\CreateCommentDTO;
use App\Data\Comment\DeleteCommentDTO;
use App\Data\Comment\FilterCommentDTO;
use App\Data\Comment\ReportCommentDTO;
use App\Data\Comment\UpdateCommentDTO;
use App\Enums\CommentStatus;
use App\Events\Comment\CommentApprovedEvent;
use App\Events\Comment\CommentCreatedEvent;
use App\Events\Comment\CommentDeletedEvent;
use App\Events\Comment\CommentReportedEvent;
use App\Events\Comment\CommentUpdatedEvent;
use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use App\Repositories\Contracts\CommentRepositoryInterface;
use App\Services\Interfaces\CommentServiceInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Event;

final class CommentService implements CommentServiceInterface
{
    public function __construct(
        private readonly CommentRepositoryInterface $commentRepository
    ) {}

    /**
     * Approve a comment (using route model binding)
     */
    public function approveComment(Comment $comment, ApproveCommentDTO $dto, User $approvedBy): Comment
    {
        $updateData = [
            'status' => CommentStatus::APPROVED,
            'approved_at' => now(),
            'approved_by' => $approvedBy->id,
        ];

        $dtoData = $dto->toArray();
        if (! empty($dtoData)) {
            $updateData = array_merge($updateData, $dtoData);
        }

        $this->commentRepository->update($comment->id, $updateData);

        /** @var Comment $freshComment */
        $freshComment = $comment->fresh(['user', 'article']);

        Event::dispatch(new CommentApprovedEvent($freshComment));

        return $freshComment;
    }

    /**
     * Delete a comment (using route model binding)
     */
    public function deleteComment(Comment $comment, DeleteCommentDTO $dto, User $deletedBy): void
    {
        $updateData = [
            'deleted_by' => $deletedBy->id,
            'deleted_at' => now(),
        ];

        $dtoData = $dto->toArray();
        if (! empty($dtoData)) {
            $updateData = array_merge($updateData, ['deleted_reason' => $dto->reason]);
        }

        $this->commentRepository->update($comment->id, $updateData);

        // Force delete to completely remove from database
        // Need to use repository query to find with trashed
        $commentToDelete = $this->commentRepository->query()
            ->withTrashed()
            ->where('id', $comment->id)
            ->firstOrFail();

        Event::dispatch(new CommentDeletedEvent($commentToDelete));

        $commentToDelete->forceDelete();
    }

    /**
     * Get comment by ID
     *
     * @throws ModelNotFoundException
     */
    public function getCommentById(int $commentId): Comment
    {
        return $this->commentRepository->query()
            ->with(['user', 'article'])
            ->findOrFail($commentId);
    }

    /**
     * Get comments with filters
     *
     * @return LengthAwarePaginator<int, Comment>
     */
    public function getComments(FilterCommentDTO $dto): LengthAwarePaginator
    {
        $query = $this->commentRepository->query()
            ->with(['user:id,name,email', 'article:id,title,slug', 'approver:id,name,email', 'deletedBy:id,name,email']);

        $this->applyFilters($query, $dto);

        return $query->orderBy($dto->sortBy, $dto->sortOrder)->paginate($dto->perPage);
    }

    /**
     * Create a new comment
     */
    public function createComment(Article $article, CreateCommentDTO $dto, User $user): Comment
    {
        // If parent comment is provided, verify it exists and belongs to the same article
        if ($dto->parentCommentId !== null) {
            $parentComment = $this->commentRepository->findOrFail($dto->parentCommentId);
            if ($parentComment->article_id !== $article->id) {
                throw new \InvalidArgumentException(__('common.parent_comment_mismatch'));
            }
        }

        $commentData = array_merge($dto->toArray(), [
            'user_id' => $user->id,
            'status' => CommentStatus::PENDING,
        ]);

        $comment = $this->commentRepository->create($commentData);

        /** @var Comment $freshComment */
        $freshComment = $comment->fresh(['user', 'article']);

        Event::dispatch(new CommentCreatedEvent($freshComment));

        return $freshComment;
    }

    /**
     * Update a comment
     */
    public function updateComment(Comment $comment, UpdateCommentDTO $dto): Comment
    {
        $this->commentRepository->update($comment->id, $dto->toArray());

        /** @var Comment $freshComment */
        $freshComment = $comment->fresh(['user', 'article']);

        Event::dispatch(new CommentUpdatedEvent($freshComment));

        return $freshComment;
    }

    /**
     * Report a comment
     */
    public function reportComment(Comment $comment, ReportCommentDTO $dto): Comment
    {
        $this->commentRepository->update($comment->id, [
            'report_count' => $comment->report_count + 1,
            'last_reported_at' => now(),
            'report_reason' => $dto->getReason(),
        ]);

        $comment->refresh();

        /** @var Comment $freshComment */
        $freshComment = $comment->fresh(['user', 'article']);

        Event::dispatch(new CommentReportedEvent($freshComment));

        return $freshComment;
    }

    /**
     * Get own comments for a user
     *
     * @return LengthAwarePaginator<int, Comment>
     */
    public function getOwnComments(User $user, int $page = 1, int $perPage = 15): LengthAwarePaginator
    {
        return $this->commentRepository->query()
            ->where('user_id', $user->id)
            ->with(['user:id,name,email', 'article:id,title,slug'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Apply filters to the query
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Comment>  $query
     */
    private function applyFilters(\Illuminate\Database\Eloquent\Builder $query, FilterCommentDTO $dto): void
    {
        if ($dto->status !== null) {
            $query->where('status', $dto->status);
        }

        if ($dto->search !== null) {
            $query->where('content', 'like', "%{$dto->search}%");
        }

        if ($dto->userId !== null) {
            $query->where('user_id', $dto->userId);
        }

        if ($dto->articleId !== null) {
            $query->where('article_id', $dto->articleId);
        }

        if ($dto->parentCommentId !== null) {
            $query->where('parent_comment_id', $dto->parentCommentId);
        }

        if ($dto->approvedBy !== null) {
            $query->where('approved_by', $dto->approvedBy);
        }

        if ($dto->hasReports !== null) {
            if ($dto->hasReports) {
                $query->where('report_count', '>', 0);
            } else {
                $query->where('report_count', 0);
            }
        }
    }

    /**
     * Get paginated comments for an article (with 1 child level or for a parent comment).
     *
     * Loads the comment's user, count of replies, and top replies (limited by $repliesPerPage).
     *
     * @param  Article  $article  The article model instance.
     * @param  int|null  $parentId  The ID of the parent comment (if loading child comments).
     * @param  int  $perPage  Number of parent comments per page.
     * @param  int  $page  Current page number.
     * @param  int  $repliesPerPage  Number of child comments per parent.
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, Comment>
     */
    public function getPaginatedCommentsWithReplies(
        Article $article,
        ?int $parentId = null,
        int $perPage = 10,
        int $page = 1,
        int $repliesPerPage = 3
    ): \Illuminate\Pagination\LengthAwarePaginator {
        $articleId = $article->id;
        $query = $this->commentRepository->query()
            ->where('article_id', $articleId)
            ->when($parentId !== null, fn ($q) => $q->where('parent_comment_id', $parentId))
            ->when($parentId === null, fn ($q) => $q->whereNull('parent_comment_id'))
            ->orderBy('created_at');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        /** @var \Illuminate\Database\Eloquent\Collection<int, Comment> $comments */
        $comments = $paginator->getCollection();

        $comments->load(['user']);
        $comments->loadCount('replies');

        // Collect IDs of parent comments
        $parentCommentIds = $comments->pluck('id');

        // Fetch replies in batch (LIMIT repliesPerPage per parent)
        $replies = $this->commentRepository->query()
            ->whereIn('parent_comment_id', $parentCommentIds)
            ->with('user')
            ->withCount('replies')
            ->orderBy('created_at')
            ->get()
            ->groupBy('parent_comment_id');

        // Attach limited replies to each comment
        $comments->each(function (Comment $comment) use ($replies, $repliesPerPage) {
            $replyCollection = $replies[$comment->id] ?? collect();
            $limitedReplies = $replyCollection->take($repliesPerPage);
            $comment->setRelation('replies_page', $limitedReplies);
        });

        // Replace the collection on paginator so it's returned with relations loaded
        return $paginator->setCollection($comments);
    }
}
