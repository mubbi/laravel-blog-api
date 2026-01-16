<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Category;

use App\Data\Category\UpdateCategoryDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Category\UpdateCategoryRequest;
use App\Http\Resources\V1\Category\CategoryResource;
use App\Models\Category;
use App\Services\Interfaces\CategoryServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Taxonomy Management', weight: 2)]
final class UpdateCategoryController extends Controller
{
    public function __construct(
        private readonly CategoryServiceInterface $categoryService
    ) {}

    /**
     * Update Category
     *
     * Updates an existing category with optional parent/child relationship changes.
     * Categories can be reorganized hierarchically by changing the parent relationship.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `edit_categories` permission.
     *
     * **Route Parameters:**
     * - `category` (Category, required): The category model instance to update
     *
     * **Request Body:**
     * - `name` (optional, string, max:255, unique): Category name
     * - `slug` (optional, string, max:255, unique): URL-friendly identifier (auto-generated from name if name is provided)
     * - `parent_id` (optional, integer|null, exists:categories,id): Parent category ID (null to make it root)
     *
     * **Response:**
     * Returns the updated category object with all details including parent relationship.
     *
     * @response array{status: true, message: string, data: CategoryResource}
     */
    public function __invoke(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        try {
            $dto = UpdateCategoryDTO::fromRequest($request);
            $updatedCategory = $this->categoryService->updateCategory($category, $dto);

            return response()->apiSuccess(
                new CategoryResource($updatedCategory->load('parent')),
                __('common.category_updated_successfully')
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
