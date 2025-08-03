<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Comment;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Comment\ApproveCommentRequest;
use App\Http\Resources\V1\Comment\CommentResource;
use App\Models\Comment;
use App\Services\CommentService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[Group('Admin - Comments', weight: 2)]
final class ApproveCommentController extends Controller
{
    public function __construct(
        private readonly CommentService $commentService
    ) {}

    /**
     * Approve a comment
     *
     * Approve a pending comment for publication
     *
     * @response array{status: true, message: string, data: CommentResource}
     */
    public function __invoke(ApproveCommentRequest $request, int $id): JsonResponse
    {
        try {
            $comment = $this->commentService->approveComment($id, $request->validated());

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
            return response()->apiError(
                __('common.comment_not_found'),
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $e) {
            /**
             * Internal server error
             *
             * @status 500
             *
             * @body array{status: false, message: string, data: null, error: null}
             */
            return response()->apiError(
                __('common.something_went_wrong'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
