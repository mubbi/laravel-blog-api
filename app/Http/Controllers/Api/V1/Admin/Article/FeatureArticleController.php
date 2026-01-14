<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Article;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Article\FeatureArticleRequest;
use App\Http\Resources\V1\Admin\Article\ArticleManagementResource;
use App\Models\Article;
use App\Services\Interfaces\ArticleFeatureServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Admin - Article Management', weight: 2)]
final class FeatureArticleController extends Controller
{
    public function __construct(
        private readonly ArticleFeatureServiceInterface $articleFeatureService
    ) {}

    /**
     * Feature Article (Admin Only)
     *
     * Marks an article as featured, making it prominently displayed in featured sections
     * and listings. Featured articles are typically highlighted on the homepage, featured
     * sections, or special collections. This is a toggle operation - calling it on an
     * already featured article will unfeature it.
     *
     * **Access Control:**
     * - **Admin users only**: This action affects the entire application's display, so only
     *   administrators with `feature_posts` permission can feature articles
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `feature_posts` permission.
     *
     * **Route Parameters:**
     * - `article` (Article, required): The article model instance to feature/unfeature
     *
     * **Response:**
     * Returns the updated article object with the featured status toggled. The article's
     * `is_featured` flag will be updated accordingly.
     *
     * **Note:** Featured articles should typically be published articles, though the system
     * may allow featuring articles in other statuses depending on implementation.
     *
     * @response array{status: true, message: string, data: ArticleManagementResource}
     */
    public function __invoke(Article $article, FeatureArticleRequest $request): JsonResponse
    {
        try {
            $article = $this->articleFeatureService->featureArticle($article);

            return response()->apiSuccess(
                new ArticleManagementResource($article),
                __('common.article_featured_successfully')
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
