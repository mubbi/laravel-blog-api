<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Article;

use App\Data\FilterArticleDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Article\GetArticlesRequest;
use App\Http\Resources\MetaResource;
use App\Http\Resources\V1\Article\ArticleResource;
use App\Services\ArticleService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Articles', weight: 1)]
final class GetArticlesController extends Controller
{
    public function __construct(
        private readonly ArticleService $articleService
    ) {}

    /**
     * Get Paginated List of Published Articles
     *
     * Retrieves a paginated list of published articles with comprehensive filtering, sorting,
     * and search capabilities. This public endpoint returns only published articles by default
     * and supports filtering by categories, tags, authors, publication dates, and search terms.
     *
     * **Query Parameters (all optional):**
     * - `page` (integer, min:1, default: 1): Page number for pagination
     * - `per_page` (integer, min:1, max:100, default: 15): Number of articles per page
     * - `search` (string, max:255): Search term to filter articles by title or content
     * - `status` (enum: draft|review|published|archived, default: published): Article status filter
     * - `category_slug` (string|array): Filter by category slug(s). Can be a single slug or array of slugs
     * - `tag_slug` (string|array): Filter by tag slug(s). Can be a single slug or array of slugs
     * - `author_id` (integer): Filter articles by specific author user ID
     * - `created_by` (integer): Filter articles by creator user ID (may differ from author in multi-author scenarios)
     * - `published_after` (date, Y-m-d format): Filter articles published on or after this date
     * - `published_before` (date, Y-m-d format): Filter articles published on or before this date
     * - `sort_by` (enum: title|published_at|created_at|updated_at, default: published_at): Field to sort by
     * - `sort_direction` (enum: asc|desc, default: desc): Sort direction
     *
     * **Response:**
     * Returns a paginated collection of articles with metadata including total count, current page,
     * per page limit, and pagination links. Each article includes full content, author information,
     * categories, tags, and publication metadata.
     *
     * @unauthenticated
     *
     * @response array{status: true, message: string, data: array{articles: ArticleResource[], meta: MetaResource}}
     */
    public function __invoke(GetArticlesRequest $request): JsonResponse
    {
        try {
            $dto = FilterArticleDTO::fromPublicRequest($request);

            $articles = $this->articleService->getArticles($dto);

            $articleCollection = ArticleResource::collection($articles);

            /**
             * Successful articles retrieval
             */
            $articleCollectionData = $articleCollection->response()->getData(true);

            // Ensure we have the expected array structure
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
