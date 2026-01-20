<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Article;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Article\ShowArticleRequest;
use App\Http\Resources\V1\Article\ArticleManagementResource;
use App\Http\Resources\V1\Article\ArticleResource;
use App\Models\Article;
use App\Services\Interfaces\ArticleManagementServiceInterface;
use App\Services\Interfaces\ArticleServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Articles', weight: 2)]
final class ShowArticleController extends Controller
{
    public function __construct(
        private readonly ArticleManagementServiceInterface $articleManagementService,
        private readonly ArticleServiceInterface $articleService
    ) {}

    /**
     * Get Single Article
     *
     * Returns article based on user permissions:
     * - Users with view_posts permission: Article by ID with management details (all statuses)
     * - Unauthenticated/No permission: Published article by slug
     *
     * @response array{status: true, message: string, data: ArticleResource|ArticleManagementResource}
     */
    public function __invoke(ShowArticleRequest $request, ?string $slug = null): JsonResponse
    {
        try {
            $hasPermission = $request->hasViewPostsPermission();

            // Get slug from route parameter
            $routeSlug = $request->route('slug');

            // Ensure slug is a string (route parameter should be string, but handle edge cases)
            if ($routeSlug !== null && ! is_string($routeSlug)) {
                $routeSlug = null;
            }

            $slug = (string) ($routeSlug ?? $slug ?? '');

            if ($slug === '') {
                return response()->apiError(
                    __('common.article_not_found'),
                    Response::HTTP_NOT_FOUND
                );
            }

            // Load article by slug
            try {
                $article = $this->articleService->getArticleBySlug($slug);
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                return response()->apiError(
                    __('common.article_not_found'),
                    Response::HTTP_NOT_FOUND
                );
            }

            // If user has permission, check if they can view this article for management
            if ($hasPermission && $request->canViewArticle($article)) {
                $article = $this->articleManagementService->loadArticleRelationshipsOnModel($article);

                return response()->apiSuccess(
                    new ArticleManagementResource($article),
                    __('common.success')
                );
            }

            // Public access - only published articles (service already filters to published)
            return response()->apiSuccess(
                new ArticleResource($article),
                __('common.success')
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
