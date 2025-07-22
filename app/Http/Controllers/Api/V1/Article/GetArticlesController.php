<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Article;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Article\GetArticlesRequest;
use App\Http\Resources\Api\V1\Article\ArticleResource;
use App\Services\ArticleService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[Group('Articles', weight: 1)]
class GetArticlesController extends Controller
{
    public function __construct(
        private readonly ArticleService $articleService
    ) {}

    /**
     * Get Articles List
     *
     * Retrieve a paginated list of articles with optional filtering by category, tags, author, and search terms
     *
     * @unauthenticated
     *
     * @response array{status: true, message: string, data: array{data: ArticleResource[], links: array, meta: array}}
     */
    public function __invoke(GetArticlesRequest $request): JsonResponse
    {
        try {
            $params = $request->withDefaults();

            $articles = $this->articleService->getArticles($params);

            $articleCollection = ArticleResource::collection($articles);

            /**
             * Successful articles retrieval
             */
            $articleCollectionData = $articleCollection->response()->getData(true);

            // Ensure we have the expected array structure
            if (! is_array($articleCollectionData) || ! isset($articleCollectionData['data'], $articleCollectionData['meta'])) {
                throw new \RuntimeException('Unexpected response format from ArticleResource collection');
            }

            return response()->apiSuccess(
                [
                    'articles' => $articleCollectionData['data'],
                    'meta' => $articleCollectionData['meta'],
                ],
                __('common.success')
            );
        } catch (\Throwable $e) {
            /**
             * Internal server error
             *
             * @status 500
             *
             * @body array{status: false, message: string, data: null, error: null}
             */
            return response()->apiError(
                __('common.something_went_wrong'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
