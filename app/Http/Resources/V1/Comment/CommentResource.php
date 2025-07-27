<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Comment;

use App\Http\Resources\V1\Auth\UserResource;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Comment
 */
class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'content' => $this->content,
            'created_at' => $this->created_at,
            'replies_count' => $this->replies_count,
            'replies' => CommentResource::collection($this->whenLoaded('replies_page')),
        ];
    }
}
