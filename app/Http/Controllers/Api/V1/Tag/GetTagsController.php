<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Tag;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Tag\TagResource;
use App\Services\Interfaces\TagServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Tags', weight: 2)]
final class GetTagsController extends Controller
{
    public function __construct(private readonly TagServiceInterface $tagService) {}

    /**
     * Get All Article Tags
     *
     * Retrieves a complete list of all available article tags in the system. Tags provide
     * flexible labeling and cross-referencing of articles. This endpoint returns all tags
     * with their metadata including slug, name, and article counts. Tags are commonly used
     * for filtering, searching, and discovering related content.
     *
     * **Response:**
     * Returns an array of all tags with their associated metadata. Each tag includes its
     * unique identifier, slug, display name, and the total number of published articles
     * associated with that tag.
     *
     * **Note:** This endpoint returns all tags regardless of whether they are associated
     * with articles. Tags without articles will show an article count of 0.
     *
     * @unauthenticated
     *
     * @response array{status: true, message: string, data: TagResource[]}
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $tags = $this->tagService->getAllTags();

            return response()->apiSuccess(
                TagResource::collection($tags),
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
