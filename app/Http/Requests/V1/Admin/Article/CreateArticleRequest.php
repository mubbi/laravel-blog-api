<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Admin\Article;

use App\Enums\ArticleAuthorRole;
use App\Rules\MediaAccessible;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property-read string $slug
 * @property-read string $title
 * @property-read string|null $subtitle
 * @property-read string|null $excerpt
 * @property-read string $content_markdown
 * @property-read string|null $content_html
 * @property-read int|null $featured_media_id
 * @property-read string|null $published_at
 * @property-read string|null $meta_title
 * @property-read string|null $meta_description
 * @property-read array<int>|null $category_ids
 * @property-read array<int>|null $tag_ids
 * @property-read array<array{user_id: int, role: string}>|null $authors
 */
final class CreateArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasPermission('create_posts');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        assert($user !== null, 'User should be authenticated');

        return [
            'slug' => ['required', 'string', 'max:255', Rule::unique('articles', 'slug')],
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content_markdown' => ['required', 'string'],
            'content_html' => ['nullable', 'string'],
            'featured_media_id' => ['nullable', 'integer', 'exists:media,id', new MediaAccessible($user)],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'authors' => ['nullable', 'array'],
            'authors.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'authors.*.role' => ['nullable', 'string', Rule::enum(ArticleAuthorRole::class)],
        ];
    }

    /**
     * Get the default values for missing parameters
     *
     * @return array<string, mixed>
     */
    public function withDefaults(): array
    {
        return $this->validated();
    }
}
