<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Category;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Category\CategoryResource;
use App\Services\ArticleService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[Group('Categories', weight: 2)]
class GetCategoriesController extends Controller
{
    public function __construct(private readonly ArticleService $articleService) {}

    /**
     * Get All Categories
     *
     * @unauthenticated
     *
     * @response array{status: true, message: string, data: CategoryResource[]}
     */
    public function __invoke(): JsonResponse
    {
        try {
            $categories = $this->articleService->getAllCategories();

            return response()->apiSuccess(
                CategoryResource::collection($categories),
                __('common.success')
            );
        } catch (\Throwable $e) {
            return response()->apiError(
                __('common.error'),
                Response::HTTP_INTERNAL_SERVER_ERROR,
                null,
                $e->getMessage()
            );
        }
    }
}
