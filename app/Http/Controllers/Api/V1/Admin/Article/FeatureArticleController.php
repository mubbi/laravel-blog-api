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
     * Feature Article
     *
     * Mark an article as featured
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
            return response()->apiError(
                __('common.article_not_found'),
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $e) {
            return response()->apiError(
                __('common.something_went_wrong'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
