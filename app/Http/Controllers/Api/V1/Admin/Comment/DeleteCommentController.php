<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Comment;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Comment\DeleteCommentRequest;
use App\Services\CommentService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[Group('Admin - Comments', weight: 2)]
final class DeleteCommentController extends Controller
{
    public function __construct(
        private readonly CommentService $commentService
    ) {}

    /**
     * Delete a comment
     *
     * Permanently delete a comment from the system
     *
     * @response array{status: true, message: string, data: null}
     */
    public function __invoke(DeleteCommentRequest $request, int $id): JsonResponse
    {
        try {
            $this->commentService->deleteComment($id, $request->validated());

            return response()->apiSuccess(
                null,
                __('common.comment_deleted')
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
