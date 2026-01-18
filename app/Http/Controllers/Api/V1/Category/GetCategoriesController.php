<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Category;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Category\GetCategoriesRequest;
use App\Http\Resources\V1\Category\CategoryResource;
use App\Services\Interfaces\CategoryServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Categories', weight: 2)]
final class GetCategoriesController extends Controller
{
    public function __construct(private readonly CategoryServiceInterface $categoryService) {}

    /**
     * Get All Article Categories
     *
     * Retrieves a complete list of all available article categories in the system. Categories
     * are used to organize and classify articles. This endpoint returns all categories with
     * their metadata including slug, name, description, and article counts. Categories are
     * typically displayed in navigation menus and filter interfaces.
     *
     * **Response:**
     * Returns an array of all categories with their associated metadata. Each category includes
     * its unique identifier, slug, display name, description (if available), and the total
     * number of published articles in that category.
     *
     * **Note:** This endpoint returns all categories regardless of whether they contain articles.
     * Categories without articles will show an article count of 0.
     *
     * @unauthenticated
     *
     * @response array{status: true, message: string, data: CategoryResource[]}
     */
    public function __invoke(GetCategoriesRequest $request): JsonResponse
    {
        try {
            $categories = $this->categoryService->getAllCategories();

            return response()->apiSuccess(
                CategoryResource::collection($categories),
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
