<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Tag;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Tag\TagResource;
use App\Services\ArticleService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[Group('Tags', weight: 2)]
class GetTagsController extends Controller
{
    public function __construct(private readonly ArticleService $articleService) {}

    /**
     * Get All Tags
     *
     * @unauthenticated
     *
     * @response array{status: true, message: string, data: TagResource[]}
     */
    public function __invoke(): JsonResponse
    {
        try {
            $tags = $this->articleService->getAllTags();

            return response()->apiSuccess(
                TagResource::collection($tags),
                __('common.success')
            );
        } catch (\Throwable $e) {
            return response()->apiError(
                __('common.error'),
                Response::HTTP_INTERNAL_SERVER_ERROR,
                null,
                $e->getMessage()
            );
        }
    }
}
