<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Comment;

use App\Data\FilterCommentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Comment\GetCommentsRequest;
use App\Http\Resources\MetaResource;
use App\Http\Resources\V1\Comment\CommentResource;
use App\Services\CommentService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Admin - Comments', weight: 2)]
final class GetCommentsController extends Controller
{
    public function __construct(
        private readonly CommentService $commentService
    ) {}

    /**
     * Get Paginated List of Comments (Admin)
     *
     * Retrieves a paginated list of all comments in the system with comprehensive admin filtering
     * and sorting capabilities. Unlike the public endpoint, this includes comments in all statuses
     * (pending, approved, rejected) and provides filtering by user, article, status, and search
     * functionality for content moderation workflows.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `comment_moderate` permission.
     *
     * **Query Parameters (all optional):**
     * - `search` (string, max:255): Search term to filter comments by content
     * - `status` (enum: pending|approved|rejected): Filter comments by moderation status
     * - `user_id` (integer): Filter comments by specific user ID
     * - `article_id` (integer): Filter comments by specific article ID
     * - `sort_by` (enum: created_at|updated_at|content|user_id|article_id, default: created_at): Field to sort by
     * - `sort_order` (enum: asc|desc, default: desc): Sort order
     * - `per_page` (integer, min:1, max:100, default: 15): Number of comments per page
     *
     * **Response:**
     * Returns a paginated collection of comments with full details including moderation status,
     * user information, article association, and metadata. Includes pagination metadata with
     * total count, current page, per page limit, and pagination links.
     *
     * @response array{status: true, message: string, data: array{comments: CommentResource[], meta: MetaResource}}
     */
    public function __invoke(GetCommentsRequest $request): JsonResponse
    {
        try {
            $dto = FilterCommentDTO::fromRequest($request);
            $comments = $this->commentService->getComments($dto);

            $commentCollection = CommentResource::collection($comments);

            /**
             * Successful comments retrieval
             */
            $commentCollectionData = $commentCollection->response()->getData(true);

            // Ensure we have the expected array structure
            if (! is_array($commentCollectionData) || ! isset($commentCollectionData['data'], $commentCollectionData['meta'])) {
                throw new \RuntimeException(__('common.unexpected_response_format'));
            }

            return response()->apiSuccess(
                [
                    'comments' => $commentCollectionData['data'],
                    'meta' => MetaResource::make($commentCollectionData['meta']),
                ],
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
