<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Article;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\ArticleService;
use App\Support\Helper;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Articles', weight: 1)]
final class DislikeArticleController extends Controller
{
    public function __construct(
        private readonly ArticleService $articleService
    ) {}

    /**
     * Dislike an Article
     *
     * Allows users to dislike a published article. This endpoint supports both authenticated
     * and anonymous users. If a user is authenticated, the dislike is tracked by their user ID.
     * If the user is not authenticated, the dislike is tracked by their IP address.
     *
     * **Behavior:**
     * - If the user/IP has already disliked the article, the existing dislike is returned
     * - If the user/IP previously liked the article, the like is removed and a dislike is created
     * - Each user/IP can only have one like or dislike per article
     *
     * **Route Parameters:**
     * - `article` (string, required): Article slug identifier (route model binding)
     *
     * **Response:**
     * Returns a success message indicating that the article has been disliked. The response
     * includes the article information with updated dislike counts.
     *
     * **Note:** This endpoint only works with published articles. Draft or archived articles
     * cannot be disliked through this public endpoint.
     *
     * @unauthenticated
     *
     * @response array{status: true, message: string, data: null}
     */
    public function __invoke(Article $article, Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $userId = $user !== null ? $user->id : null;
            $ipAddress = $userId === null ? Helper::getRealIpAddress($request) : null;

            $this->articleService->dislikeArticle($article, $userId, $ipAddress);

            return response()->apiSuccess(
                null,
                __('article.disliked_successfully')
            );
        } catch (InvalidArgumentException $e) {
            /**
             * Article not published or invalid request
             *
             * @status 404
             *
             * @body array{status: false, message: string, data: null, error: null}
             */
            return response()->apiError(
                __('common.not_found'),
                Response::HTTP_NOT_FOUND
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
