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
            $parentId = $params['parent_id'] !== null ? (int) $params['parent_id'] : null;

            $commentsDataResponse = CommentResource::collection($this->articleService->getArticleComments(
                $article->id,
                $parentId,
                (int) $params['per_page'],
                (int) $params['page']
            ));
            /** @var array{data: array, meta: array} $commentsData */
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
