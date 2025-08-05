<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CommentStatus;
use App\Models\Comment;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CommentService
{
    /**
     * Approve a comment
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ModelNotFoundException
     */
    public function approveComment(int $commentId, array $data): Comment
    {
        $comment = Comment::findOrFail($commentId);

        $comment->update([
            'status' => CommentStatus::APPROVED,
            'admin_note' => $data['admin_note'] ?? null,
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        return $comment->load(['user', 'article']);
    }

    /**
     * Delete a comment
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ModelNotFoundException
     */
    public function deleteComment(int $commentId, array $data): void
    {
        $comment = Comment::findOrFail($commentId);

        $comment->update([
            'deleted_reason' => $data['reason'] ?? null,
            'deleted_by' => auth()->id(),
            'deleted_at' => now(),
        ]);

        // Force delete to completely remove from database
        $comment->forceDelete();
    }

    /**
     * Get comment by ID
     *
     * @throws ModelNotFoundException
     */
    public function getCommentById(int $commentId): Comment
    {
        return Comment::with(['user', 'article'])->findOrFail($commentId);
    }

    /**
     * Get comments with filters
     *
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, Comment>
     */
    public function getComments(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Comment::with(['user:id,name,email', 'article:id,title,slug', 'approver:id,name,email', 'deletedBy:id,name,email']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            /** @var string $searchTerm */
            $searchTerm = $filters['search'];
            $query->where('content', 'like', "%{$searchTerm}%");
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['article_id'])) {
            $query->where('article_id', $filters['article_id']);
        }

        if (isset($filters['parent_comment_id'])) {
            $query->where('parent_comment_id', $filters['parent_comment_id']);
        }

        if (isset($filters['approved_by'])) {
            $query->where('approved_by', $filters['approved_by']);
        }

        if (isset($filters['has_reports'])) {
            if ((bool) $filters['has_reports']) {
                $query->where('report_count', '>', 0);
            } else {
                $query->where('report_count', 0);
            }
        }

        /** @var string $sortBy */
        $sortBy = $filters['sort_by'] ?? 'created_at';
        /** @var string $sortOrder */
        $sortOrder = $filters['sort_order'] ?? 'desc';
        /** @var int $perPage */
        $perPage = $filters['per_page'] ?? 15;

        return $query->orderBy($sortBy, $sortOrder)->paginate($perPage);
    }
}
