<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Article;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Article\GetCommentsRequest;
use App\Http\Resources\MetaResource;
use App\Http\Resources\V1\Comment\CommentResource;
use App\Models\Article;
use App\Services\ArticleService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[Group('Comments', weight: 2)]
class GetCommentsController extends Controller
{
    public function __construct(private readonly ArticleService $articleService) {}

    /**
     * Get Comments List
     *
     * Retrieve a paginated list of comments for an article (with 1 child level)
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
                $article->id,
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
        } catch (\Throwable $e) {
            return response()->apiError(
                __('common.something_went_wrong'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
