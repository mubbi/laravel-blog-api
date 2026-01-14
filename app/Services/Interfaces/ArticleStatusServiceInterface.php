<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\Article;
use App\Models\User;

interface ArticleStatusServiceInterface
{
    /**
     * Approve an article (using route model binding)
     */
    public function approveArticle(Article $article, User $approvedBy): Article;

    /**
     * Reject an article (set to draft)
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function rejectArticle(int $id, int $rejectedBy): Article;

    /**
     * Archive an article (using route model binding)
     */
    public function archiveArticle(Article $article): Article;

    /**
     * Restore an article from archive (using route model binding)
     */
    public function restoreArticle(Article $article): Article;

    /**
     * Trash an article (using route model binding)
     */
    public function trashArticle(Article $article): Article;

    /**
     * Restore an article from trash (using route model binding)
     */
    public function restoreFromTrash(Article $article): Article;

    /**
     * Permanently delete an article
     */
    public function deleteArticle(int $id): bool;
}
