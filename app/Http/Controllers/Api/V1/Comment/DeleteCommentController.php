<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Comment;

use App\Data\Comment\DeleteCommentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Comment\DeleteCommentRequest;
use App\Models\Comment;
use App\Services\Interfaces\CommentServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Comments', weight: 2)]
final class DeleteCommentController extends Controller
{
    public function __construct(
        private readonly CommentServiceInterface $commentService
    ) {}

    /**
     * Permanently Delete Comment (Admin)
     *
     * Permanently deletes a comment from the system. This action cannot be undone and will
     * remove the comment and all associated data. Used for removing inappropriate, spam, or
     * otherwise unwanted comments. This is different from rejecting a comment, which changes
     * its status but preserves the data.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `delete_comments` permission.
     *
     * **Route Parameters:**
     * - `comment` (Comment, required): The comment model instance to delete
     *
     * **Request Body:**
     * - `reason` (optional, string, max:500): Optional reason for deleting the comment (for audit purposes)
     *
     * **Response:**
     * Returns a success message confirming the comment has been deleted. The response body
     * contains no data (null) as the comment no longer exists.
     *
     * **Note:** This operation cannot be reversed. Consider rejecting comments instead if
     * you want to preserve the data for audit purposes.
     *
     * @response array{status: true, message: string, data: null}
     */
    public function __invoke(DeleteCommentRequest $request, Comment $comment): JsonResponse
    {
        try {
            $user = $request->user();
            assert($user !== null);

            // Check authorization using policy (allows users to delete their own comments)
            if (! $user->can('delete', $comment)) {
                return response()->apiError(
                    __('common.unauthorized'),
                    Response::HTTP_FORBIDDEN
                );
            }

            $dto = DeleteCommentDTO::fromRequest($request);
            $this->commentService->deleteComment($comment, $dto, $user);

            return response()->apiSuccess(
                null,
                __('common.comment_deleted_successfully')
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
