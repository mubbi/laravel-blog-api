<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Article;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Article\ArchiveArticleRequest;
use App\Http\Resources\V1\Admin\Article\ArticleManagementResource;
use App\Models\Article;
use App\Services\ArticleStatusService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Article Management', weight: 2)]
final class ArchiveArticleController extends Controller
{
    public function __construct(
        private readonly ArticleStatusService $articleStatusService
    ) {}

    /**
     * Archive Article
     *
     * Archives an article, moving it from active status to archived status. Archived articles
     * are typically hidden from public view but preserved for historical purposes. This is
     * useful for content that is no longer relevant but should be retained.
     *
     * **Access Control:**
     * - **Authenticated users**: Can archive their own articles (requires `edit_posts` permission)
     * - **Admin users** (with `edit_others_posts` permission): Can archive any article in the system
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability. Users need `edit_posts` permission
     * for their own articles, or `edit_others_posts` permission to archive any article.
     *
     * **Route Parameters:**
     * - `article` (Article, required): The article model instance to archive
     *
     * **Response:**
     * Returns the updated article object with the archived status. The article's status
     * will be changed to "archived" and it will be hidden from public endpoints.
     *
     * **Note:** Only published articles should typically be archived. Archived articles can
     * be restored using the restore endpoint.
     *
     * @response array{status: true, message: string, data: ArticleManagementResource}
     */
    public function __invoke(Article $article, ArchiveArticleRequest $request): JsonResponse
    {
        try {
            $article = $this->articleStatusService->archiveArticle($article);

            return response()->apiSuccess(
                new ArticleManagementResource($article),
                __('common.article_archived_successfully')
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
