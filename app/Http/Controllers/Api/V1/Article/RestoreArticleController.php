<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Article;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Article\RestoreArticleRequest;
use App\Http\Resources\V1\Article\ArticleManagementResource;
use App\Models\Article;
use App\Services\Interfaces\ArticleStatusServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Article Management', weight: 2)]
final class RestoreArticleController extends Controller
{
    public function __construct(
        private readonly ArticleStatusServiceInterface $articleStatusService
    ) {}

    /**
     * Restore Article from Archive
     *
     * Restores an archived article back to published status, making it visible to public users again.
     * This endpoint is used to reactivate articles that were previously archived.
     *
     * **Access Control:**
     * - **Authenticated users**: Can restore their own archived articles (requires `edit_posts` permission)
     * - **Admin users** (with `edit_others_posts` permission): Can restore any archived article in the system
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability. Users need `edit_posts` permission
     * for their own articles, or `edit_others_posts` permission to restore any article.
     *
     * **Route Parameters:**
     * - `article` (Article, required): The article model instance to restore
     *
     * **Response:**
     * Returns the updated article object with the restored status. The article's status
     * will be changed from "archived" to "published" and it will become visible through
     * public endpoints again.
     *
     * @response array{status: true, message: string, data: ArticleManagementResource}
     */
    public function __invoke(Article $article, RestoreArticleRequest $request): JsonResponse
    {
        try {
            $article = $this->articleStatusService->restoreArticle($article);

            return response()->apiSuccess(
                new ArticleManagementResource($article),
                __('common.article_restored_successfully')
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
