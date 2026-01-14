<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Article;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Article\GetCommentsRequest;
use App\Http\Resources\MetaResource;
use App\Http\Resources\V1\Comment\CommentResource;
use App\Models\Article;
use App\Services\Interfaces\ArticleServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Comments', weight: 2)]
final class GetCommentsController extends Controller
{
    public function __construct(private readonly ArticleServiceInterface $articleService) {}

    /**
     * Get Paginated Comments for an Article
     *
     * Retrieves a paginated list of approved comments for a specific article. Supports nested
     * comment threading with one level of child comments. Comments are returned in a hierarchical
     * structure where parent comments contain their direct replies. Only approved comments are
     * returned through this public endpoint.
     *
     * **Route Parameters:**
     * - `article` (string, required): Article slug identifier (route model binding)
     *
     * **Query Parameters (all optional):**
     * - `page` (integer, min:1, default: 1): Page number for pagination
     * - `per_page` (integer, min:1, max:100, default: 10): Number of comments per page
     * - `parent_id` (integer, nullable): Filter to show only child comments of a specific parent comment ID
     *
     * **Response:**
     * Returns a paginated collection of comments with metadata. Each comment includes user
     * information, comment content, creation date, and nested child comments (if any).
     * The response structure supports threaded comment displays in the UI.
     *
     * @unauthenticated
     *
     * @response array{status: true, message: string, data: array{comments: CommentResource[], meta: MetaResource}}
     */
    public function __invoke(GetCommentsRequest $request, Article $article): JsonResponse
    {
        $params = $request->withDefaults();

        try {
            /** @var mixed $parentIdInput */
            $parentIdInput = $request->input('parent_id');
            /** @var mixed $commentIdInput */
            $commentIdInput = $request->input('comment_id');
            /** @var mixed $userIdInput */
            $userIdInput = $request->input('user_id');
            $parentId = is_numeric($parentIdInput) ? (int) $parentIdInput : null;
            $commentId = is_numeric($commentIdInput) ? (int) $commentIdInput : null;
            $userId = is_numeric($userIdInput) ? (int) $userIdInput : null;

            /** @var mixed $perPageParam */
            $perPageParam = $params['per_page'] ?? 10;
            /** @var mixed $pageParam */
            $pageParam = $params['page'] ?? 1;
            $perPage = (int) $perPageParam;
            $page = (int) $pageParam;
            $commentsDataResponse = CommentResource::collection($this->articleService->getArticleComments(
                $article,
                $parentId,
                $perPage,
                $page
            ));
            /** @var array{data: array<int, mixed>, meta: array<string, mixed>} $commentsData */
            $commentsData = $commentsDataResponse->response()->getData(true);

            return response()->apiSuccess(
                [
                    'comments' => $commentsData['data'],
                    'meta' => MetaResource::make($commentsData['meta']),
                ],
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
