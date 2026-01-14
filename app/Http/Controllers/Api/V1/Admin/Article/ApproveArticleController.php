<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Article;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Article\ApproveArticleRequest;
use App\Http\Resources\V1\Admin\Article\ArticleManagementResource;
use App\Models\Article;
use App\Services\Interfaces\ArticleStatusServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Admin - Article Management', weight: 2)]
final class ApproveArticleController extends Controller
{
    public function __construct(
        private readonly ArticleStatusServiceInterface $articleStatusService
    ) {}

    /**
     * Approve and Publish Article (Admin Only)
     *
     * Approves an article and changes its status to published, making it visible to public users.
     * This endpoint is used in content moderation workflows to review and approve articles that
     * are in draft or review status. The approving admin's ID is recorded for audit purposes.
     *
     * **Access Control:**
     * - **Admin users only**: This is an admin-only action for content moderation
     * - Regular users cannot approve articles, even their own
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `approve_posts` permission.
     *
     * **Route Parameters:**
     * - `article` (Article, required): The article model instance to approve
     *
     * **Response:**
     * Returns the updated article object with the approved status and published date set.
     * The article's status will be changed to "published" and it will become visible through
     * public endpoints.
     *
     * **Note:** Only articles in draft or review status can be approved. Already published
     * articles remain published and do not need approval.
     *
     * @response array{status: true, message: string, data: ArticleManagementResource}
     */
    public function __invoke(Article $article, ApproveArticleRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            assert($user !== null);

            $article = $this->articleStatusService->approveArticle($article, $user);

            return response()->apiSuccess(
                new ArticleManagementResource($article),
                __('common.article_approved_successfully')
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
