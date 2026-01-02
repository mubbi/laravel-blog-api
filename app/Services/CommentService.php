<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\ApproveCommentDTO;
use App\Data\DeleteCommentDTO;
use App\Data\FilterCommentDTO;
use App\Enums\CommentStatus;
use App\Events\Comment\CommentApprovedEvent;
use App\Events\Comment\CommentDeletedEvent;
use App\Models\Comment;
use App\Repositories\Contracts\CommentRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Event;

final class CommentService
{
    public function __construct(
        private readonly CommentRepositoryInterface $commentRepository
    ) {}

    /**
     * Approve a comment
     *
     * @throws ModelNotFoundException
     */
    public function approveComment(int $commentId, ApproveCommentDTO $dto, int $approvedBy): Comment
    {
        $comment = $this->commentRepository->findOrFail($commentId);

        $updateData = [
            'status' => CommentStatus::APPROVED,
            'approved_at' => now(),
            'approved_by' => $approvedBy,
        ];

        $dtoData = $dto->toArray();
        if (! empty($dtoData)) {
            $updateData = array_merge($updateData, $dtoData);
        }

        $this->commentRepository->update($commentId, $updateData);

        /** @var Comment $freshComment */
        $freshComment = $comment->fresh(['user', 'article']);

        Event::dispatch(new CommentApprovedEvent($freshComment));

        return $freshComment;
    }

    /**
     * Delete a comment
     *
     * @throws ModelNotFoundException
     */
    public function deleteComment(int $commentId, DeleteCommentDTO $dto, int $deletedBy): void
    {
        $comment = $this->commentRepository->findOrFail($commentId);

        $updateData = [
            'deleted_by' => $deletedBy,
            'deleted_at' => now(),
        ];

        $dtoData = $dto->toArray();
        if (! empty($dtoData)) {
            $updateData = array_merge($updateData, ['deleted_reason' => $dto->reason]);
        }

        $this->commentRepository->update($commentId, $updateData);

        // Force delete to completely remove from database
        // Need to use repository query to find with trashed
        $commentToDelete = $this->commentRepository->query()
            ->withTrashed()
            ->where('id', $commentId)
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
}
