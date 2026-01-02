<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Article;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Article\ShowArticleRequest;
use App\Http\Resources\V1\Admin\Article\ArticleManagementResource;
use App\Services\ArticleManagementService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Admin - Article Management', weight: 2)]
final class ShowArticleController extends Controller
{
    public function __construct(
        private readonly ArticleManagementService $articleManagementService
    ) {}

    /**
     * Get Single Article by ID (Admin)
     *
     * Retrieves detailed information about a specific article by its ID. This admin endpoint
     * provides complete article data including all statuses (draft, review, published, archived),
     * management flags (featured, pinned), report information, approval details, and full
     * administrative metadata. Used for viewing and managing individual articles in admin panels.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `view_posts` permission.
     *
     * **Route Parameters:**
     * - `id` (integer, required): The unique identifier of the article to retrieve
     *
     * **Response:**
     * Returns the complete article object with all management details including status, featured/pinned
     * flags, report counts, approval information, author details, categories, tags, and all metadata.
     * Unlike the public endpoint, this includes articles in all statuses.
     *
     * @response array{status: true, message: string, data: ArticleManagementResource}
     */
    public function __invoke(int $id, ShowArticleRequest $request): JsonResponse
    {
        try {
            $article = $this->articleManagementService->getArticleById($id);

            return response()->apiSuccess(
                new ArticleManagementResource($article),
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
