<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Article;

use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Article
 */
class ArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'excerpt' => $this->excerpt,
            'content_html' => $this->content_html,
            'content_markdown' => $this->content_markdown,
            'featured_image' => $this->featured_image,
            'status' => $this->status,
            'published_at' => $this->published_at?->toISOString(),
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Relationships
            // Original Author
            'author' => $this->whenLoaded('author', function () use ($request) {
                return $this->author ? [
                    'id' => $this->author->id,
                    'name' => $this->author->name,
                    'email' => $this->when((bool) $request->user()?->hasRole(UserRole::ADMINISTRATOR->value), $this->author->email),
                    'avatar_url' => $this->author->avatar_url,
                    'bio' => $this->author->bio,
                    'twitter' => $this->author->twitter,
                    'facebook' => $this->author->facebook,
                    'linkedin' => $this->author->linkedin,
                    'github' => $this->author->github,
                    'website' => $this->author->website,
                ] : null;
            }),

            'categories' => $this->whenLoaded('categories', function () {
                /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Category> $categories */
                $categories = $this->categories;

                return $categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                    ];
                })->values()->all();
            }),

            'tags' => $this->whenLoaded('tags', function () {
                /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tag> $tags */
                $tags = $this->tags;

                return $tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'slug' => $tag->slug,
                    ];
                })->values()->all();
            }),

            // Co-Authors
            'authors' => $this->whenLoaded('authors', function () use ($request) {
                /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $authors */
                $authors = $this->authors;

                return $authors->map(function ($author) use ($request) {
                    return [
                        'id' => $author->id,
                        'name' => $author->name,
                        'email' => $this->when((bool) $request->user()?->hasRole(UserRole::ADMINISTRATOR->value), $author->email),
                        'avatar_url' => $author->avatar_url,
                        'bio' => $author->bio,
                        'twitter' => $author->twitter,
                        'facebook' => $author->facebook,
                        'linkedin' => $author->linkedin,
                        'github' => $author->github,
                        'website' => $author->website,
                    ];
                })->values()->all();
            }),

            'comments_count' => $this->whenCounted('comments'),
        ];
    }
}
