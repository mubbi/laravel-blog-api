<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Article;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Article\FeatureArticleRequest;
use App\Http\Resources\V1\Admin\Article\ArticleManagementResource;
use App\Services\ArticleManagementService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[Group('Admin - Article Management', weight: 2)]
final class FeatureArticleController extends Controller
{
    public function __construct(
        private readonly ArticleManagementService $articleManagementService
    ) {}

    /**
     * Feature Article (Admin)
     *
     * Marks an article as featured, making it prominently displayed in featured sections
     * and listings. Featured articles are typically highlighted on the homepage, featured
     * sections, or special collections. This is a toggle operation - calling it on an
     * already featured article will unfeature it.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `feature_posts` permission.
     *
     * **Route Parameters:**
     * - `id` (integer, required): The unique identifier of the article to feature/unfeature
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
    public function __invoke(int $id, FeatureArticleRequest $request): JsonResponse
    {
        try {
            $article = $this->articleManagementService->featureArticle($id);

            return response()->apiSuccess(
                new ArticleManagementResource($article),
                __('common.article_featured_successfully')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            /**
             * Article not found
             *
             * @status 404
             *
             * @body array{status: false, message: string, data: null, error: null}
             */
            return $this->handleException($e, $request);
        } catch (\Throwable $e) {
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
