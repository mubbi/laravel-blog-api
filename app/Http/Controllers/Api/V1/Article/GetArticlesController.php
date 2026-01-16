<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Article;

use App\Data\Article\FilterArticleDTO;
use App\Data\Article\FilterArticleManagementDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Article\GetArticlesRequest;
use App\Http\Resources\MetaResource;
use App\Http\Resources\V1\Article\ArticleManagementResource;
use App\Http\Resources\V1\Article\ArticleResource;
use App\Services\Interfaces\ArticleManagementServiceInterface;
use App\Services\Interfaces\ArticleServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Articles', weight: 2)]
final class GetArticlesController extends Controller
{
    public function __construct(
        private readonly ArticleManagementServiceInterface $articleManagementService,
        private readonly ArticleServiceInterface $articleService
    ) {}

    /**
     * Get Paginated List of Articles
     *
     * Returns articles based on user permissions:
     * - Users with view_posts permission: All article statuses with management filters
     * - Unauthenticated/No permission: Only published articles with public filters
     *
     * @response array{status: true, message: string, data: array{articles: ArticleResource[]|ArticleManagementResource[], meta: MetaResource}}
     */
    public function __invoke(GetArticlesRequest $request): JsonResponse
    {
        try {
            $hasPermission = $request->hasViewPostsPermission();

            if ($hasPermission) {
                // User has view_posts permission - use management service
                $dto = FilterArticleManagementDTO::fromRequest($request);
                $userIdForFiltering = $request->getUserIdForFiltering();
                $articles = $this->articleManagementService->getArticles($dto, $userIdForFiltering);
                $articleCollection = ArticleManagementResource::collection($articles);
            } else {
                // No permission - use public service (only published articles)
                $dto = FilterArticleDTO::fromArray($request->validated());
                $articles = $this->articleService->getArticles($dto);
                $articleCollection = ArticleResource::collection($articles);
            }

            $articleCollectionData = $articleCollection->response()->getData(true);

            if (! is_array($articleCollectionData) || ! isset($articleCollectionData['data'], $articleCollectionData['meta'])) {
                throw new RuntimeException(__('common.unexpected_response_format'));
            }

            return response()->apiSuccess(
                [
                    'articles' => $articleCollectionData['data'],
                    'meta' => MetaResource::make($articleCollectionData['meta']),
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
