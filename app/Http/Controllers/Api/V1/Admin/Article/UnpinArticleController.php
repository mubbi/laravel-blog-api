<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Article;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Article\UnpinArticleRequest;
use App\Http\Resources\V1\Admin\Article\ArticleManagementResource;
use App\Models\Article;
use App\Services\ArticleFeatureService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Admin - Article Management', weight: 2)]
final class UnpinArticleController extends Controller
{
    public function __construct(
        private readonly ArticleFeatureService $articleFeatureService
    ) {}

    /**
     * Unpin Article (Admin Only)
     *
     * Removes the pinned status from an article, allowing it to appear in normal listing order.
     * This endpoint is used to unpin articles that were previously pinned to the top of listings.
     *
     * **Access Control:**
     * - **Admin users only**: This action affects the entire application's display, so only
     *   administrators with `feature_posts` permission can unpin articles
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `feature_posts` permission.
     *
     * **Route Parameters:**
     * - `article` (Article, required): The article model instance to unpin
     *
     * **Response:**
     * Returns the updated article object with the pinned status removed. The article's
     * `is_pinned` flag will be set to false and `pinned_at` timestamp will be cleared.
     *
     * @response array{status: true, message: string, data: ArticleManagementResource}
     */
    public function __invoke(Article $article, UnpinArticleRequest $request): JsonResponse
    {
        try {
            $article = $this->articleFeatureService->unpinArticle($article);

            return response()->apiSuccess(
                new ArticleManagementResource($article),
                __('common.article_unpinned_successfully')
            );
        } catch (Throwable $e) {
            /**
             * Internal server error
             *
             * @status 500
             *
             * @body array{status: false, message: string, data: null, error: null}
             */
            return $this->handleException($e, $request);
        }
    }
}
