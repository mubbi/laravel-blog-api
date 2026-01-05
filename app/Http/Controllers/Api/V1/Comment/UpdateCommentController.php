<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Comment;

use App\Data\UpdateCommentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Comment\UpdateCommentRequest;
use App\Http\Resources\V1\Comment\CommentResource;
use App\Models\Comment;
use App\Services\CommentService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Comments', weight: 2)]
final class UpdateCommentController extends Controller
{
    public function __construct(
        private readonly CommentService $commentService
    ) {}

    /**
     * Update Comment
     *
     * Updates an existing comment. Users can only update their own comments, while administrators
     * can update any comment. The comment content can be modified, and the updated timestamp will
     * be automatically refreshed.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability. Users can only update their own comments,
     * while administrators with `edit_comments` permission can update any comment.
     *
     * **Route Parameters:**
     * - `comment` (Comment, required): The comment model instance to update
     *
     * **Request Body:**
     * - `content` (string, required, min:1, max:5000): The updated comment content
     *
     * **Response:**
     * Returns the updated comment object with the new content and refreshed timestamps.
     *
     * **Note:** Only the comment owner or an administrator can update a comment.
     *
     * @response array{status: true, message: string, data: CommentResource}
     */
    public function __invoke(UpdateCommentRequest $request, Comment $comment): JsonResponse
    {
        try {
            $dto = UpdateCommentDTO::fromRequest($request);
            $comment = $this->commentService->updateComment($comment, $dto);

            return response()->apiSuccess(
                new CommentResource($comment),
                __('common.comment_updated_successfully')
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
