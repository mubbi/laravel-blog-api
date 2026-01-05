<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Article;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Article\ShowArticleRequest;
use App\Http\Resources\V1\Admin\Article\ArticleManagementResource;
use App\Models\Article;
use App\Services\Interfaces\ArticleManagementServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Article Management', weight: 2)]
final class ShowArticleController extends Controller
{
    public function __construct(
        private readonly ArticleManagementServiceInterface $articleManagementService
    ) {}

    /**
     * Get Single Article by ID
     *
     * Retrieves detailed information about a specific article by its ID. This endpoint provides
     * complete article data including all statuses (draft, review, published, archived),
     * management flags (featured, pinned), report information, approval details, and full
     * administrative metadata.
     *
     * **Access Control:**
     * - **Authenticated users**: Can view their own articles
     * - **Admin users** (with `edit_others_posts` permission): Can view any article in the system
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `view_posts` permission.
     *
     * **Route Parameters:**
     * - `article` (Article, required): The article model instance to retrieve
     *
     * **Response:**
     * Returns the complete article object with all management details including status, featured/pinned
     * flags, report counts, approval information, author details, categories, tags, and all metadata.
     * Unlike the public endpoint, this includes articles in all statuses.
     *
     * @response array{status: true, message: string, data: ArticleManagementResource}
     */
    public function __invoke(Article $article, ShowArticleRequest $request): JsonResponse
    {
        try {
            $article = $this->articleManagementService->loadArticleRelationshipsOnModel($article);

            return response()->apiSuccess(
                new ArticleManagementResource($article),
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
