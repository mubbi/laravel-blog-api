<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Comment;

use App\Data\CreateCommentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Comment\CreateCommentRequest;
use App\Http\Resources\V1\Comment\CommentResource;
use App\Models\Article;
use App\Services\Interfaces\CommentServiceInterface;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Comments', weight: 2)]
final class CreateCommentController extends Controller
{
    public function __construct(
        private readonly CommentServiceInterface $commentService
    ) {}

    /**
     * Create Comment
     *
     * Creates a new comment on an article. This endpoint is available to authenticated users.
     * Comments are created with a "pending" status and require moderation before being visible
     * to public users. Users can create top-level comments or reply to existing comments by
     * providing a parent_comment_id.
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability. Only authenticated users can create comments.
     *
     * **Route Parameters:**
     * - `article` (Article, required): The article model instance to comment on (route model binding)
     *
     * **Request Body:**
     * - `content` (string, required, min:1, max:5000): The comment content
     * - `parent_comment_id` (integer, optional): The ID of the parent comment if this is a reply
     *
     * **Response:**
     * Returns the newly created comment object with pending status. The comment will need to be
     * approved by an administrator before it becomes visible to public users.
     *
     * **Note:** If a parent_comment_id is provided, it must belong to the same article.
     *
     * @response array{status: true, message: string, data: CommentResource}
     */
    public function __invoke(CreateCommentRequest $request, Article $article): JsonResponse
    {
        try {
            $user = $request->user();
            assert($user !== null);
            $dto = CreateCommentDTO::fromRequest($request, $article);
            $comment = $this->commentService->createComment($article, $dto, $user);

            return response()->apiSuccess(
                new CommentResource($comment),
                __('common.comment_created_successfully')
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
