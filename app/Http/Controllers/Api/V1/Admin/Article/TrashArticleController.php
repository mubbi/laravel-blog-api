<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Article;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Article\TrashArticleRequest;
use App\Http\Resources\V1\Admin\Article\ArticleManagementResource;
use App\Models\Article;
use App\Services\Interfaces\ArticleStatusServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Article Management', weight: 2)]
final class TrashArticleController extends Controller
{
    public function __construct(
        private readonly ArticleStatusServiceInterface $articleStatusService
    ) {}

    /**
     * Trash Article
     *
     * Moves an article to trash status, effectively soft-deleting it. Trashed articles are
     * hidden from public view and most admin listings, but can be restored if needed. This
     * is a safer alternative to permanent deletion.
     *
     * **Access Control:**
     * - **Authenticated users**: Can trash their own articles (requires `delete_posts` permission)
     * - **Admin users** (with `delete_others_posts` permission): Can trash any article in the system
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability. Users need `delete_posts` permission
     * for their own articles, or `delete_others_posts` permission to trash any article.
     *
     * **Route Parameters:**
     * - `article` (Article, required): The article model instance to trash
     *
     * **Response:**
     * Returns the updated article object with the trashed status. The article's status
     * will be changed to "trashed" and it will be hidden from public and most admin endpoints.
     *
     * **Note:** Trashed articles can be restored using the restore-from-trash endpoint.
     * For permanent deletion, use the delete endpoint instead.
     *
     * @response array{status: true, message: string, data: ArticleManagementResource}
     */
    public function __invoke(Article $article, TrashArticleRequest $request): JsonResponse
    {
        try {
            $article = $this->articleStatusService->trashArticle($article);

            return response()->apiSuccess(
                new ArticleManagementResource($article),
                __('common.article_trashed_successfully')
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
