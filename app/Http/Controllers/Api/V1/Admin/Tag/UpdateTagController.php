<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Tag;

use App\Data\UpdateTagDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Tag\UpdateTagRequest;
use App\Http\Resources\V1\Tag\TagResource;
use App\Models\Tag;
use App\Services\TagService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Admin - Taxonomy Management', weight: 2)]
final class UpdateTagController extends Controller
{
    public function __construct(
        private readonly TagService $tagService
    ) {}

    /**
     * Update Tag
     *
     * Updates an existing tag.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `edit_tags` permission.
     *
     * **Route Parameters:**
     * - `tag` (Tag, required): The tag model instance to update
     *
     * **Request Body:**
     * - `name` (optional, string, max:255, unique): Tag name
     * - `slug` (optional, string, max:255, unique): URL-friendly identifier (auto-generated from name if name is provided)
     *
     * **Response:**
     * Returns the updated tag object with all details.
     *
     * @response array{status: true, message: string, data: TagResource}
     */
    public function __invoke(UpdateTagRequest $request, Tag $tag): JsonResponse
    {
        try {
            $dto = UpdateTagDTO::fromRequest($request);
            $updatedTag = $this->tagService->updateTag($tag, $dto);

            return response()->apiSuccess(
                new TagResource($updatedTag),
                __('common.tag_updated_successfully')
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
