<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Comment;

use App\Data\ReportCommentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Comment\ReportCommentRequest;
use App\Http\Resources\V1\Comment\CommentResource;
use App\Models\Comment;
use App\Services\CommentService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Comments', weight: 2)]
final class ReportCommentController extends Controller
{
    public function __construct(
        private readonly CommentService $commentService
    ) {}

    /**
     * Report Comment
     *
     * Creates a report record for a comment, typically used to flag content that violates
     * community guidelines, contains inappropriate material, or requires administrative review.
     * Reports help administrators identify and address problematic comments. The report reason
     * is stored for administrative review and audit purposes.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability. Only authenticated users can report comments.
     *
     * **Route Parameters:**
     * - `comment` (Comment, required): The comment model instance to report
     *
     * **Request Body:**
     * - `reason` (optional, string, max:1000): Detailed reason or description for reporting the comment
     *
     * **Response:**
     * Returns the updated comment object with the report count incremented. The comment's
     * report information is updated to reflect the new report, and administrators can review
     * reported comments through the admin interface.
     *
     * **Note:** Comments can have multiple reports. The report count helps identify comments
     * that may need administrative attention.
     *
     * @response array{status: true, message: string, data: CommentResource}
     */
    public function __invoke(ReportCommentRequest $request, Comment $comment): JsonResponse
    {
        try {
            $dto = ReportCommentDTO::fromRequest($request);
            $comment = $this->commentService->reportComment($comment, $dto);

            return response()->apiSuccess(
                new CommentResource($comment),
                __('common.comment_reported_successfully')
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
