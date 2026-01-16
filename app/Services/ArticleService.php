<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\Article\FilterArticleDTO;
use App\Enums\ArticleReactionType;
use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleLike;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use App\Services\Article\ArticleFilterService;
use App\Services\Interfaces\ArticleServiceInterface;
use App\Services\Interfaces\CommentServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ArticleService implements ArticleServiceInterface
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articleRepository,
        private readonly CommentServiceInterface $commentService,
        private readonly ArticleFilterService $filterService
    ) {}

    /**
     * Get articles with filters and pagination
     *
     * @return LengthAwarePaginator<int, Article>
     */
    public function getArticles(FilterArticleDTO $dto): LengthAwarePaginator
    {
        return $this->filterService->getFilteredArticles($dto);
    }

    /**
     * Get a single article by slug
     * Cached for 1 hour to reduce database load on frequently accessed articles
     */
    public function getArticleBySlug(string $slug): Article
    {
        $cacheKey = \App\Constants\CacheKeys::articleBySlug($slug);

        /** @var int $ttl */
        $ttl = (int) config('cache-ttl.keys.article_by_slug', 3600);

        /** @var Article $article */
        $article = \Illuminate\Support\Facades\Cache::remember(
            $cacheKey,
            $ttl,
            function () use ($slug) {
                return $this->articleRepository->query()
                    ->with([
                        'author:id,name,email,avatar_url,bio,twitter,facebook,linkedin,github,website',
                        'approver:id,name,email,avatar_url',
                        'updater:id,name,email,avatar_url',
                        'categories:id,name,slug',
                        'tags:id,name,slug',
                        'authors:id,name,email,avatar_url,bio,twitter,facebook,linkedin,github,website',
                        'featuredMedia:id,url,name,alt_text',
                    ])
                    ->withCount('comments')
                    ->where('slug', $slug)
                    ->firstOrFail();
            }
        );

        return $article;
    }

    /**
     * Get paginated comments for an article (with 1 child level or for a parent comment).
     *
     * Delegates to CommentService for pagination logic.
     *
     * @param  Article  $article  The article model instance.
     * @param  int|null  $parentId  The ID of the parent comment (if loading child comments).
     * @param  int  $perPage  Number of parent comments per page.
     * @param  int  $page  Current page number.
     * @param  int  $repliesPerPage  Number of child comments per parent.
     * @return Paginator<int, \App\Models\Comment>
     */
    public function getArticleComments(
        Article $article,
        ?int $parentId = null,
        int $perPage = 10,
        int $page = 1,
        int $repliesPerPage = 3
    ): Paginator {
        return $this->commentService->getPaginatedCommentsWithReplies(
            $article,
            $parentId,
            $perPage,
            $page,
            $repliesPerPage
        );
    }

    /**
     * Like an article
     *
     * @param  Article  $article  The article model instance
     * @param  int|null  $userId  The user ID if authenticated, null for anonymous
     * @param  string|null  $ipAddress  The IP address for anonymous likes
     *
     * @throws InvalidArgumentException
     */
    public function likeArticle(Article $article, ?int $userId = null, ?string $ipAddress = null): ArticleLike
    {
        $this->validateArticleIsPublished($article, ArticleReactionType::LIKE);
        $this->validateUserOrIp($userId, $ipAddress);

        return $this->toggleArticleReaction($article->id, ArticleReactionType::LIKE, $userId, $ipAddress);
    }

    /**
     * Dislike an article
     *
     * @param  Article  $article  The article model instance
     * @param  int|null  $userId  The user ID if authenticated, null for anonymous
     * @param  string|null  $ipAddress  The IP address for anonymous dislikes
     *
     * @throws InvalidArgumentException
     */
    public function dislikeArticle(Article $article, ?int $userId = null, ?string $ipAddress = null): ArticleLike
    {
        $this->validateArticleIsPublished($article, ArticleReactionType::DISLIKE);
        $this->validateUserOrIp($userId, $ipAddress);

        return $this->toggleArticleReaction($article->id, ArticleReactionType::DISLIKE, $userId, $ipAddress);
    }

    /**
     * Validate that the article is published
     *
     * @param  Article  $article  The article model instance
     * @param  ArticleReactionType  $reactionType  The reaction type being performed
     *
     * @throws InvalidArgumentException
     */
    private function validateArticleIsPublished(Article $article, ArticleReactionType $reactionType): void
    {
        if ($article->status !== ArticleStatus::PUBLISHED || $article->published_at === null) {
            $message = $reactionType === ArticleReactionType::LIKE
                ? __('article.must_be_published_to_like')
                : __('article.must_be_published_to_dislike');

            throw new InvalidArgumentException($message);
        }
    }

    /**
     * Validate that either userId or ipAddress is provided, but not both
     *
     * @param  int|null  $userId  The user ID if authenticated, null for anonymous
     * @param  string|null  $ipAddress  The IP address for anonymous reactions
     *
     * @throws InvalidArgumentException
     */
    private function validateUserOrIp(?int $userId, ?string $ipAddress): void
    {
        if ($userId === null && $ipAddress === null) {
            throw new InvalidArgumentException(__('article.user_or_ip_required'));
        }

        if ($userId !== null && $ipAddress !== null) {
            throw new InvalidArgumentException(__('article.cannot_set_both_user_and_ip'));
        }
    }

    /**
     * Toggle article reaction (like or dislike) with transaction handling
     *
     * @param  int  $articleId  The article ID
     * @param  ArticleReactionType  $reactionType  The reaction type
     * @param  int|null  $userId  The user ID if authenticated, null for anonymous
     * @param  string|null  $ipAddress  The IP address for anonymous reactions
     */
    private function toggleArticleReaction(
        int $articleId,
        ArticleReactionType $reactionType,
        ?int $userId,
        ?string $ipAddress
    ): ArticleLike {
        return DB::transaction(function () use ($articleId, $reactionType, $userId, $ipAddress) {
            // Check if user/IP already has this reaction
            $existingReaction = $this->findExistingReaction($articleId, $reactionType, $userId, $ipAddress);

            if ($existingReaction !== null) {
                return $existingReaction;
            }

            // Remove opposite reaction if it exists
            $oppositeType = $reactionType->opposite();
            $this->removeReaction($articleId, $oppositeType, $userId, $ipAddress);

            // Create the new reaction
            $reaction = ArticleLike::create([
                'article_id' => $articleId,
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'type' => $reactionType,
            ]);

            // Dispatch appropriate event
            $article = $this->articleRepository->findOrFail($articleId);
            if ($reactionType === \App\Enums\ArticleReactionType::LIKE) {
                \Illuminate\Support\Facades\Event::dispatch(new \App\Events\Article\ArticleLikedEvent($article, $reaction));
            } else {
                \Illuminate\Support\Facades\Event::dispatch(new \App\Events\Article\ArticleDislikedEvent($article, $reaction));
            }

            return $reaction;
        });
    }

    /**
     * Find an existing reaction for the given article and user/IP
     *
     * @param  int  $articleId  The article ID
     * @param  ArticleReactionType  $reactionType  The reaction type
     * @param  int|null  $userId  The user ID if authenticated, null for anonymous
     * @param  string|null  $ipAddress  The IP address for anonymous reactions
     */
    private function findExistingReaction(
        int $articleId,
        ArticleReactionType $reactionType,
        ?int $userId,
        ?string $ipAddress
    ): ?ArticleLike {
        return ArticleLike::where('article_id', $articleId)
            ->where('type', $reactionType->value)
            ->when($userId !== null, fn ($q) => $q->where('user_id', $userId)->whereNull('ip_address'))
            ->when($userId === null, fn ($q) => $q->whereNull('user_id')->where('ip_address', $ipAddress))
            ->first();
    }

    /**
     * Remove a reaction for the given article and user/IP
     *
     * @param  int  $articleId  The article ID
     * @param  ArticleReactionType  $reactionType  The reaction type to remove
     * @param  int|null  $userId  The user ID if authenticated, null for anonymous
     * @param  string|null  $ipAddress  The IP address for anonymous reactions
     */
    private function removeReaction(
        int $articleId,
        ArticleReactionType $reactionType,
        ?int $userId,
        ?string $ipAddress
    ): void {
        ArticleLike::where('article_id', $articleId)
            ->where('type', $reactionType->value)
            ->when($userId !== null, fn ($q) => $q->where('user_id', $userId)->whereNull('ip_address'))
            ->when($userId === null, fn ($q) => $q->whereNull('user_id')->where('ip_address', $ipAddress))
            ->delete();
    }
}
