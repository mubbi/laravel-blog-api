<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Article;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Article\GetArticlesRequest;
use App\Http\Resources\MetaResource;
use App\Http\Resources\V1\Admin\Article\ArticleManagementResource;
use App\Services\ArticleManagementService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[Group('Admin - Article Management', weight: 2)]
final class GetArticlesController extends Controller
{
    public function __construct(
        private readonly ArticleManagementService $articleManagementService
    ) {}

    /**
     * Get Articles List
     *
     * Retrieve a paginated list of articles with admin filters and management capabilities
     *
     * @response array{status: true, message: string, data: array{articles: ArticleManagementResource[], meta: MetaResource}}
     */
    public function __invoke(GetArticlesRequest $request): JsonResponse
    {
        try {
            $params = $request->withDefaults();
            $articles = $this->articleManagementService->getArticles($params);
            $articleCollection = ArticleManagementResource::collection($articles);
            $articleCollectionData = $articleCollection->response()->getData(true);

            if (! is_array($articleCollectionData) || ! isset($articleCollectionData['data'], $articleCollectionData['meta'])) {
                throw new \RuntimeException(__('common.unexpected_response_format'));
            }

            return response()->apiSuccess(
                [
                    'articles' => $articleCollectionData['data'],
                    'meta' => MetaResource::make($articleCollectionData['meta']),
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
