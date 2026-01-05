<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Comment;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Comment\GetOwnCommentsRequest;
use App\Http\Resources\MetaResource;
use App\Http\Resources\V1\Comment\CommentResource;
use App\Services\CommentService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

#[Group('Comments', weight: 2)]
final class GetOwnCommentsController extends Controller
{
    public function __construct(
        private readonly CommentService $commentService
    ) {}

    /**
     * Get Own Comments
     *
     * Retrieves a paginated list of comments created by the authenticated user. This endpoint
     * allows users to view all their comments across all articles, regardless of the comment's
     * moderation status. Comments are returned in descending order by creation date (newest first).
     *
     * **Authentication & Authorization:**
     * Requires a valid Bearer token with `access-api` ability. Only authenticated users can access their own comments.
     *
     * **Query Parameters (all optional):**
     * - `page` (integer, min:1, default: 1): The page number for pagination
     * - `per_page` (integer, min:1, max:100, default: 15): Number of comments per page
     *
     * **Response:**
     * Returns a paginated collection of the user's comments with full details including status,
     * article association, and metadata. Includes pagination metadata with total count, current
     * page, per page limit, and pagination links.
     *
     * **Note:** This endpoint returns all comments created by the user, including pending, approved,
     * and rejected comments. Users can see the full status of their own comments.
     *
     * @response array{status: true, message: string, data: array{comments: CommentResource[], meta: MetaResource}}
     */
    public function __invoke(GetOwnCommentsRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            assert($user !== null);
            $params = $request->withDefaults();
            $comments = $this->commentService->getOwnComments(
                $user,
                (int) $params['page'],
                (int) $params['per_page']
            );

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
