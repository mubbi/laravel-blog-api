<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Category;

use App\Data\Category\DeleteCategoryDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Category\DeleteCategoryRequest;
use App\Models\Category;
use App\Services\Interfaces\CategoryServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Taxonomy Management', weight: 2)]
final class DeleteCategoryController extends Controller
{
    public function __construct(
        private readonly CategoryServiceInterface $categoryService
    ) {}

    /**
     * Delete Category
     *
     * Deletes a category from the system. If the category has child categories,
     * you can choose to either delete all children or move them to the parent category.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `delete_categories` permission.
     *
     * **Route Parameters:**
     * - `category` (Category, required): The category model instance to delete
     *
     * **Request Body:**
     * - `delete_children` (optional, boolean, default: false): If true, deletes all child categories recursively.
     *   If false, moves child categories to the parent category (or makes them root if no parent).
     *
     * **Response:**
     * Returns a success message confirming the category has been deleted.
     *
     * **Note:** This operation cannot be reversed. If `delete_children` is false, child categories
     * will be moved to the parent category (or become root categories if the deleted category had no parent).
     *
     * @response array{status: true, message: string, data: null}
     */
    public function __invoke(DeleteCategoryRequest $request, Category $category): JsonResponse
    {
        try {
            $dto = DeleteCategoryDTO::fromRequest($request);
            $this->categoryService->deleteCategory($category, $dto);

            return response()->apiSuccess(
                null,
                __('common.category_deleted_successfully')
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
