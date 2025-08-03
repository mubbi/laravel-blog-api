<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Comment;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Comment\GetCommentsRequest;
use App\Http\Resources\MetaResource;
use App\Http\Resources\V1\Comment\CommentResource;
use App\Services\CommentService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

#[Group('Admin - Comments', weight: 2)]
final class GetCommentsController extends Controller
{
    public function __construct(
        private readonly CommentService $commentService
    ) {}

    /**
     * Get all comments for admin management
     *
     * Retrieve a paginated list of all comments with filtering and sorting options
     *
     * @response array{status: true, message: string, data: AnonymousResourceCollection, meta: array}
     */
    public function __invoke(GetCommentsRequest $request): JsonResponse
    {
        try {
            $filters = $request->withDefaults();
            $comments = $this->commentService->getComments($filters);

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
        } catch (\Throwable $e) {
            // Log the error for debugging
            \Log::error('GetCommentsController: Exception occurred', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

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
