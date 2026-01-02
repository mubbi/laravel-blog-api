<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Article;

use App\Data\FilterArticleManagementDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Article\GetArticlesRequest;
use App\Http\Resources\MetaResource;
use App\Http\Resources\V1\Admin\Article\ArticleManagementResource;
use App\Services\ArticleManagementService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Admin - Article Management', weight: 2)]
final class GetArticlesController extends Controller
{
    public function __construct(
        private readonly ArticleManagementService $articleManagementService
    ) {}

    /**
     * Get Paginated List of Articles (Admin)
     *
     * Retrieves a paginated list of all articles in the system with comprehensive admin filtering,
     * sorting, and search capabilities. Unlike the public endpoint, this includes all article
     * statuses (draft, review, published, archived) and provides additional management filters
     * such as featured status, pinned status, and report counts.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `view_posts` permission.
     *
     * **Query Parameters (all optional):**
     * - `page` (integer, min:1, default: 1): Page number for pagination
     * - `per_page` (integer, min:1, max:100, default: 15): Number of articles per page
     * - `search` (string, max:255): Search term to filter articles by title or content
     * - `status` (enum: draft|review|published|archived): Filter articles by publication status
     * - `author_id` (integer): Filter articles by specific author user ID
     * - `category_id` (integer): Filter articles by category ID
     * - `tag_id` (integer): Filter articles by tag ID
     * - `is_featured` (boolean): Filter featured articles (true) or non-featured (false)
     * - `is_pinned` (boolean): Filter pinned articles (true) or non-pinned (false)
     * - `has_reports` (boolean): Filter articles with reports (true) or without (false)
     * - `created_after` (date, Y-m-d format): Filter articles created on or after this date
     * - `created_before` (date, Y-m-d format): Filter articles created on or before this date
     * - `published_after` (date, Y-m-d format): Filter articles published on or after this date
     * - `published_before` (date, Y-m-d format): Filter articles published on or before this date
     * - `sort_by` (enum: title|created_at|published_at|status|is_featured|is_pinned|report_count, default: created_at): Field to sort by
     * - `sort_direction` (enum: asc|desc, default: desc): Sort direction
     *
     * **Response:**
     * Returns a paginated collection of articles with full management details including status,
     * featured/pinned flags, report counts, approval information, and metadata. Includes pagination
     * metadata with total count, current page, per page limit, and pagination links.
     *
     * @response array{status: true, message: string, data: array{articles: ArticleManagementResource[], meta: MetaResource}}
     */
    public function __invoke(GetArticlesRequest $request): JsonResponse
    {
        try {
            $dto = FilterArticleManagementDTO::fromRequest($request);
            $articles = $this->articleManagementService->getArticles($dto);
            $articleCollection = ArticleManagementResource::collection($articles);
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
