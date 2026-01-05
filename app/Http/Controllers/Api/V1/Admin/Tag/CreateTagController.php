<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Tag;

use App\Data\CreateTagDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Tag\CreateTagRequest;
use App\Http\Resources\V1\Tag\TagResource;
use App\Services\TagService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Admin - Taxonomy Management', weight: 2)]
final class CreateTagController extends Controller
{
    public function __construct(
        private readonly TagService $tagService
    ) {}

    /**
     * Create Tag
     *
     * Creates a new tag.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `create_tags` permission.
     *
     * **Request Body:**
     * - `name` (required, string, max:255, unique): Tag name
     * - `slug` (optional, string, max:255, unique): URL-friendly identifier (auto-generated from name if not provided)
     *
     * **Response:**
     * Returns the newly created tag object with all details.
     * The response includes HTTP 201 Created status code.
     *
     * @response array{status: true, message: string, data: TagResource}
     */
    public function __invoke(CreateTagRequest $request): JsonResponse
    {
        try {
            $dto = CreateTagDTO::fromRequest($request);
            $tag = $this->tagService->createTag($dto);

            return response()->apiSuccess(
                new TagResource($tag),
                __('common.tag_created_successfully'),
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
