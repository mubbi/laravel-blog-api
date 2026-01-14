<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Comment;

use App\Enums\CommentStatus;
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
            'article_id' => $this->article_id,
            'user_id' => $this->user_id,
            'parent_comment_id' => $this->parent_comment_id,
            'content' => $this->content,
            'status' => $this->status->value,
            'status_display' => __('enums.comment_status.'.$this->status->value),
            'is_approved' => $this->status === CommentStatus::APPROVED,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toISOString(),
            'report_count' => $this->report_count,
            'last_reported_at' => $this->last_reported_at?->toISOString(),
            'report_reason' => $this->report_reason,
            'moderator_notes' => $this->moderator_notes,
            'admin_note' => $this->admin_note,
            'deleted_reason' => $this->deleted_reason,
            'deleted_by' => $this->deleted_by,
            'deleted_at' => $this->deleted_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'user' => $this->whenLoaded('user', function () {
                $user = $this->user;
                if ($user === null) {
                    return null;
                }

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ];
            }),
            'article' => $this->whenLoaded('article', function () {
                return [
                    'id' => $this->article->id,
                    'title' => $this->article->title,
                    'slug' => $this->article->slug,
                ];
            }),
            'approver' => $this->whenLoaded('approver', function () {
                return $this->approver ? [
                    'id' => $this->approver->id,
                    'name' => $this->approver->name,
                    'email' => $this->approver->email,
                ] : null;
            }),
            'deleted_by_user' => $this->whenLoaded('deletedBy', function () {
                return $this->deletedBy ? [
                    'id' => $this->deletedBy->id,
                    'name' => $this->deletedBy->name,
                    'email' => $this->deletedBy->email,
                ] : null;
            }),
            'replies_count' => $this->replies_count ?? 0,
            'replies' => CommentResource::collection($this->whenLoaded('replies_page')),
        ];
    }
}
