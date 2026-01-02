<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Admin\Article;

use App\Enums\ArticleStatus;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Article $resource
 */
final class ArticleManagementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'slug' => $this->resource->slug,
            'title' => $this->resource->title,
            'subtitle' => $this->resource->subtitle,
            'excerpt' => $this->resource->excerpt,
            'content_markdown' => $this->resource->content_markdown,
            'content_html' => $this->resource->content_html,
            'featured_image' => $this->resource->featured_image,
            'status' => $this->resource->status->value,
            'status_display' => $this->resource->status instanceof ArticleStatus ? __('enums.article_status.'.$this->resource->status->value) : $this->resource->status,
            'published_at' => $this->resource->published_at,
            'meta_title' => $this->resource->meta_title,
            'meta_description' => $this->resource->meta_description,
            'is_featured' => $this->resource->is_featured,
            'is_pinned' => $this->resource->is_pinned,
            'featured_at' => $this->resource->featured_at,
            'pinned_at' => $this->resource->pinned_at,
            'report_count' => $this->resource->report_count,
            'last_reported_at' => $this->resource->last_reported_at,
            'report_reason' => $this->resource->report_reason,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
            'author' => $this->whenLoaded('author', function () {
                $author = $this->resource->author;
                if ($author === null) {
                    return null;
                }

                return [
                    'id' => $author->id,
                    'name' => $author->name,
                    'email' => $author->email,
                ];
            }),
            'approver' => $this->whenLoaded('approver', function () {
                $approver = $this->resource->approver;
                if ($approver === null) {
                    return null;
                }

                return [
                    'id' => $approver->id,
                    'name' => $approver->name,
                    'email' => $approver->email,
                ];
            }, null),
            'updater' => $this->whenLoaded('updater', function () {
                $updater = $this->resource->updater;
                if ($updater === null) {
                    return null;
                }

                return [
                    'id' => $updater->id,
                    'name' => $updater->name,
                    'email' => $updater->email,
                ];
            }, null),
            'categories' => $this->whenLoaded('categories', function () {
                return $this->resource->categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                    ];
                });
            }),
            'tags' => $this->whenLoaded('tags', function () {
                return $this->resource->tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'slug' => $tag->slug,
                    ];
                });
            }),
            'comments_count' => $this->resource->comments_count ?? 0,
            'authors_count' => $this->resource->authors_count ?? 0,
            'comments' => $this->whenLoaded('comments', function () {
                return $this->resource->comments->map(function ($comment) {
                    $user = $comment->user;

                    return [
                        'id' => $comment->id,
                        'content' => $comment->content,
                        'user' => $user !== null ? [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                        ] : null,
                        'created_at' => $comment->created_at,
                    ];
                });
            }),
        ];
    }
}
