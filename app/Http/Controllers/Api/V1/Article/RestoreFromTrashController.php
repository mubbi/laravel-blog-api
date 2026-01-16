<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Article;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Article\RestoreFromTrashRequest;
use App\Http\Resources\V1\Article\ArticleManagementResource;
use App\Models\Article;
use App\Services\Interfaces\ArticleStatusServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Article Management', weight: 2)]
final class RestoreFromTrashController extends Controller
{
    public function __construct(
        private readonly ArticleStatusServiceInterface $articleStatusService
    ) {}

    /**
     * Restore Article from Trash
     *
     * Restores a trashed article back to draft status, making it available for editing and
     * republishing. This endpoint is used to recover articles that were previously moved to trash.
     *
     * **Access Control:**
     * - **Authenticated users**: Can restore their own trashed articles (requires `delete_posts` permission)
     * - **Admin users** (with `delete_others_posts` permission): Can restore any trashed article in the system
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability. Users need `delete_posts` permission
     * for their own articles, or `delete_others_posts` permission to restore any article.
     *
     * **Route Parameters:**
     * - `article` (Article, required): The article model instance to restore
     *
     * **Response:**
     * Returns the updated article object with the restored status. The article's status
     * will be changed from "trashed" to "draft", allowing it to be edited and republished.
     *
     * **Note:** Restored articles are set to draft status and will need to be approved
     * and published again before becoming visible to public users.
     *
     * @response array{status: true, message: string, data: ArticleManagementResource}
     */
    public function __invoke(Article $article, RestoreFromTrashRequest $request): JsonResponse
    {
        try {
            $article = $this->articleStatusService->restoreFromTrash($article);

            return response()->apiSuccess(
                new ArticleManagementResource($article),
                __('common.article_restored_from_trash_successfully')
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
