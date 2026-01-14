<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Article;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Article\ArticleResource;
use App\Services\Interfaces\ArticleServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Articles', weight: 1)]
final class ShowArticleController extends Controller
{
    public function __construct(
        private readonly ArticleServiceInterface $articleService
    ) {}

    /**
     * Get Single Article by Slug
     *
     * Retrieves a single published article by its unique slug identifier. This endpoint is used
     * to display individual article pages and includes all article details including content,
     * author information, categories, tags, publication date, and view count.
     *
     * **Route Parameters:**
     * - `slug` (string, required): The unique slug identifier of the article (e.g., "my-article-title")
     *
     * **Response:**
     * Returns the complete article object with all related data including author details,
     * associated categories and tags, publication metadata, and full article content.
     *
     * **Note:** This endpoint only returns published articles. Draft or archived articles
     * are not accessible through this public endpoint (see admin endpoints for those).
     *
     * @unauthenticated
     *
     * @response array{status: true, message: string, data: ArticleResource}
     */
    public function __invoke(string $slug, Request $request): JsonResponse
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
            return $this->handleException($e, $request);
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
