<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Article;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Article\ShowArticleRequest;
use App\Http\Resources\V1\Admin\Article\ArticleManagementResource;
use App\Models\Article;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[Group('Admin - Article Management', weight: 2)]
final class ShowArticleController extends Controller
{
    /**
     * Show Article
     *
     * Retrieve a single article with full admin management details
     *
     * @response array{status: true, message: string, data: ArticleManagementResource}
     */
    public function __invoke(int $id, ShowArticleRequest $request): JsonResponse
    {
        try {
            $article = Article::query()
                ->with(['author:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug', 'comments.user:id,name,email'])
                ->withCount(['comments', 'authors'])
                ->findOrFail($id);

            return response()->apiSuccess(
                new ArticleManagementResource($article),
                __('common.success')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->apiError(
                __('common.article_not_found'),
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $e) {
            return response()->apiError(
                __('common.something_went_wrong'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
