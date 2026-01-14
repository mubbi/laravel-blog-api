<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Article;

use App\Data\ReportArticleDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Article\ReportArticleRequest;
use App\Http\Resources\V1\Admin\Article\ArticleManagementResource;
use App\Models\Article;
use App\Services\Interfaces\ArticleReportServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Article Management', weight: 2)]
final class ReportArticleController extends Controller
{
    public function __construct(
        private readonly ArticleReportServiceInterface $articleReportService
    ) {}

    /**
     * Report Article
     *
     * Creates a report record for an article, typically used to flag content that violates
     * community guidelines, contains inappropriate material, or requires administrative review.
     * Reports help administrators identify and address problematic content. The report reason
     * is stored for administrative review and audit purposes.
     *
     * **Access Control:**
     * - **Authenticated users**: Can report any article (requires `report_posts` permission)
     * - This action is available to all authenticated users, not just admins
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `report_posts` permission.
     *
     * **Route Parameters:**
     * - `article` (Article, required): The article model instance to report
     *
     * **Request Body:**
     * - `reason` (optional, string, max:1000): Detailed reason or description for reporting the article
     *
     * **Response:**
     * Returns the updated article object with the report count incremented. The article's
     * report information is updated to reflect the new report, and administrators can review
     * reported articles through the admin interface.
     *
     * **Note:** Articles can have multiple reports. The report count helps identify articles
     * that may need administrative attention.
     *
     * @response array{status: true, message: string, data: ArticleManagementResource}
     */
    public function __invoke(Article $article, ReportArticleRequest $request): JsonResponse
    {
        try {
            $dto = ReportArticleDTO::fromRequest($request);
            $article = $this->articleReportService->reportArticle($article, $dto);

            return response()->apiSuccess(
                new ArticleManagementResource($article),
                __('common.article_reported_successfully')
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
