<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Data\ApproveCommentDTO;
use App\Data\CreateCommentDTO;
use App\Data\DeleteCommentDTO;
use App\Data\FilterCommentDTO;
use App\Data\ReportCommentDTO;
use App\Data\UpdateCommentDTO;
use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface CommentServiceInterface
{
    /**
     * Approve a comment (using route model binding)
     */
    public function approveComment(Comment $comment, ApproveCommentDTO $dto, User $approvedBy): Comment;

    /**
     * Delete a comment (using route model binding)
     */
    public function deleteComment(Comment $comment, DeleteCommentDTO $dto, User $deletedBy): void;

    /**
     * Get comment by ID
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getCommentById(int $commentId): Comment;

    /**
     * Get comments with filters
     *
     * @return LengthAwarePaginator<int, Comment>
     */
    public function getComments(FilterCommentDTO $dto): LengthAwarePaginator;

    /**
     * Create a new comment
     */
    public function createComment(Article $article, CreateCommentDTO $dto, User $user): Comment;

    /**
     * Update a comment
     */
    public function updateComment(Comment $comment, UpdateCommentDTO $dto): Comment;

    /**
     * Report a comment
     */
    public function reportComment(Comment $comment, ReportCommentDTO $dto): Comment;

    /**
     * Get own comments for a user
     *
     * @return LengthAwarePaginator<int, Comment>
     */
    public function getOwnComments(User $user, int $page = 1, int $perPage = 15): LengthAwarePaginator;
}
