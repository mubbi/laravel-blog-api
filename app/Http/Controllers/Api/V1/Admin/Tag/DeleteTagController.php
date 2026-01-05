<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Tag;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Tag\DeleteTagRequest;
use App\Models\Tag;
use App\Services\TagService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Admin - Taxonomy Management', weight: 2)]
final class DeleteTagController extends Controller
{
    public function __construct(
        private readonly TagService $tagService
    ) {}

    /**
     * Delete Tag
     *
     * Deletes a tag from the system.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `delete_tags` permission.
     *
     * **Route Parameters:**
     * - `tag` (Tag, required): The tag model instance to delete
     *
     * **Response:**
     * Returns a success message confirming the tag has been deleted.
     *
     * **Note:** This operation cannot be reversed.
     *
     * @response array{status: true, message: string, data: null}
     */
    public function __invoke(DeleteTagRequest $request, Tag $tag): JsonResponse
    {
        try {
            $this->tagService->deleteTag($tag);

            return response()->apiSuccess(
                null,
                __('common.tag_deleted_successfully')
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
