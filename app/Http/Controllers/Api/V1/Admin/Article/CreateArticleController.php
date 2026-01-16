<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Article;

use App\Data\CreateArticleDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Article\CreateArticleRequest;
use App\Http\Resources\V1\Admin\Article\ArticleManagementResource;
use App\Services\Interfaces\ArticleManagementServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Article Management', weight: 2)]
final class CreateArticleController extends Controller
{
    public function __construct(
        private readonly ArticleManagementServiceInterface $articleManagementService
    ) {}

    /**
     * Create New Article
     *
     * Creates a new article with the specified details. This endpoint allows authenticated users
     * to create articles with complete content, metadata, and optional scheduling. The article
     * can be created as a draft, published immediately, or scheduled for future publication.
     *
     * **Access Control:**
     * - **Authenticated users**: Can create articles (will be set as the creator)
     * - Articles created by non-admin users may require approval before publication
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `create_posts` permission.
     *
     * **Request Body:**
     * - `slug` (required, string, max:255, unique): URL-friendly identifier for the article
     * - `title` (required, string, max:255): Article title
     * - `subtitle` (optional, string, max:255): Article subtitle
     * - `excerpt` (optional, string, max:500): Short description or excerpt
     * - `content_markdown` (required, string): Article content in Markdown format
     * - `content_html` (optional, string): Article content in HTML format
     * - `featured_media_id` (optional, integer, exists:media,id): ID of featured media
     * - `published_at` (optional, date, after_or_equal:now): Publication date (for scheduling)
     * - `meta_title` (optional, string, max:255): SEO meta title
     * - `meta_description` (optional, string, max:500): SEO meta description
     * - `category_ids` (optional, array): Array of category IDs
     * - `tag_ids` (optional, array): Array of tag IDs
     * - `authors` (optional, array): Array of author objects with `user_id` and `role`
     *
     * **Scheduling Behavior:**
     * - If `published_at` is provided and is in the future, the article status will be set to "scheduled"
     * - If `published_at` is provided and is now or in the past, the article status will be set to "published"
     * - If `published_at` is not provided, the article status will be set to "draft"
     *
     * **Response:**
     * Returns the newly created article object with all details including relationships (categories,
     * tags, authors). The response includes HTTP 201 Created status code.
     *
     * @response array{status: true, message: string, data: ArticleManagementResource}
     */
    public function __invoke(CreateArticleRequest $request): JsonResponse
    {
        try {
            $dto = CreateArticleDTO::fromRequest($request);
            $article = $this->articleManagementService->createArticle($dto);

            return response()->apiSuccess(
                new ArticleManagementResource($article),
                __('common.article_created_successfully'),
                Response::HTTP_CREATED
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
