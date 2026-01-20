<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Article;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Article\PinArticleRequest;
use App\Http\Resources\V1\Article\ArticleManagementResource;
use App\Models\Article;
use App\Services\Interfaces\ArticleFeatureServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Article Management', weight: 2)]
final class PinArticleController extends Controller
{
    public function __construct(
        private readonly ArticleFeatureServiceInterface $articleFeatureService
    ) {}

    /**
     * Pin Article (Admin Only)
     *
     * Pins an article to the top of listings, making it prominently displayed. Pinned articles
     * are typically shown at the top of article lists, homepage sections, or category pages.
     * Only one article can be pinned at a time in most implementations.
     *
     * **Access Control:**
     * - **Admin users only**: This action affects the entire application's display, so only
     *   administrators with `feature_posts` permission can pin articles
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `feature_posts` permission.
     *
     * **Route Parameters:**
     * - `article` (Article, required): The article model instance to pin
     *
     * **Response:**
     * Returns the updated article object with the pinned status set. The article's
     * `is_pinned` flag will be set to true and `pinned_at` timestamp will be recorded.
     *
     * **Note:** Pinned articles should typically be published articles, though the system
     * may allow pinning articles in other statuses depending on implementation.
     *
     * @response array{status: true, message: string, data: ArticleManagementResource}
     */
    public function __invoke(Article $article, PinArticleRequest $request): JsonResponse
    {
        try {
            $article = $this->articleFeatureService->pinArticle($article);

            return response()->apiSuccess(
                new ArticleManagementResource($article),
                __('common.article_pinned_successfully')
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
