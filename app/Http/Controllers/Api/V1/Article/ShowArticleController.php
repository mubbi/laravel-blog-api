<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Article;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Article\ArticleResource;
use App\Services\ArticleService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[Group('Articles', weight: 1)]
class ShowArticleController extends Controller
{
    public function __construct(
        private readonly ArticleService $articleService
    ) {}

    /**
     * Get Article by Slug
     *
     * Retrieve a specific article by its slug identifier
     *
     * @unauthenticated
     *
     * @response array{status: true, message: string, data: ArticleResource}
     */
    public function __invoke(string $slug): JsonResponse
    {
        try {
            $article = $this->articleService->getArticleBySlug($slug);

            /**
             * Successful article retrieval
             */
            return response()->apiSuccess(
                new ArticleResource($article),
                __('common.success')
            );
        } catch (ModelNotFoundException $e) {
            /**
             * Article not found
             *
             * @status 404
             *
             * @body array{status: false, message: string, data: null, error: null}
             */
            return response()->apiError(
                __('common.not_found'),
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $e) {
            /**
             * Internal server error
             *
             * @status 500
             *
             * @body array{status: false, message: string, data: null, error: null}
             */
            return response()->apiError(
                __('common.something_went_wrong'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
