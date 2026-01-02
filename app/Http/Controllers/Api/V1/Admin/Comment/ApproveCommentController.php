<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Comment;

use App\Data\ApproveCommentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Comment\ApproveCommentRequest;
use App\Http\Resources\V1\Comment\CommentResource;
use App\Models\Comment;
use App\Services\CommentService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Admin - Comments', weight: 2)]
final class ApproveCommentController extends Controller
{
    public function __construct(
        private readonly CommentService $commentService
    ) {}

    /**
     * Approve Comment (Admin)
     *
     * Approves a pending comment, changing its status to approved and making it visible to
     * public users. This endpoint is used in comment moderation workflows to review and approve
     * comments that require moderation. Only comments in pending status can be approved.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability and `approve_comments` permission.
     *
     * **Route Parameters:**
     * - `id` (integer, required): The unique identifier of the comment to approve
     *
     * **Request Body:**
     * - `admin_note` (optional, string, max:500): Optional administrative note about the approval decision
     *
     * **Response:**
     * Returns the updated comment object with the approved status. The comment's status will
     * be changed to "approved" and it will become visible through public comment endpoints.
     *
     * **Note:** Only pending comments can be approved. Already approved or rejected comments
     * cannot be processed through this endpoint.
     *
     * @response array{status: true, message: string, data: CommentResource}
     */
    public function __invoke(ApproveCommentRequest $request, int $id): JsonResponse
    {
        try {
            $dto = ApproveCommentDTO::fromRequest($request);
            $comment = $this->commentService->approveComment($id, $dto);

            return response()->apiSuccess(
                new CommentResource($comment),
                __('common.comment_approved')
            );
        } catch (ModelNotFoundException $e) {
            /**
             * Comment not found
             *
             * @status 404
             *
             * @body array{status: false, message: string, data: null, error: null}
             */
            return $this->handleException($e, $request);
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
