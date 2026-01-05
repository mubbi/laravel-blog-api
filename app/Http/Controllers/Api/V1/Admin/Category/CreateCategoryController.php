<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Category;

use App\Data\CreateCategoryDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Category\CreateCategoryRequest;
use App\Http\Resources\V1\Category\CategoryResource;
use App\Services\CategoryService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Admin - Taxonomy Management', weight: 2)]
final class CreateCategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService
    ) {}

    /**
     * Create Category
     *
     * Creates a new category with optional parent/child relationship support.
     * Categories can be organized hierarchically with parent and child relationships.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `create_categories` permission.
     *
     * **Request Body:**
     * - `name` (required, string, max:255, unique): Category name
     * - `slug` (optional, string, max:255, unique): URL-friendly identifier (auto-generated from name if not provided)
     * - `parent_id` (optional, integer, exists:categories,id): Parent category ID for hierarchical structure
     *
     * **Response:**
     * Returns the newly created category object with all details including parent relationship.
     * The response includes HTTP 201 Created status code.
     *
     * @response array{status: true, message: string, data: CategoryResource}
     */
    public function __invoke(CreateCategoryRequest $request): JsonResponse
    {
        try {
            $dto = CreateCategoryDTO::fromRequest($request);
            $category = $this->categoryService->createCategory($dto);

            return response()->apiSuccess(
                new CategoryResource($category->load('parent')),
                __('common.category_created_successfully'),
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
