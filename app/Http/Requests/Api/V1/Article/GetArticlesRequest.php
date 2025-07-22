<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Article;

use App\Enums\ArticleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetArticlesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'page' => ['integer', 'min:1'],
            'per_page' => ['integer', 'min:1', 'max:100'],
            'search' => ['string', 'max:255'],
            'status' => [Rule::enum(ArticleStatus::class)],
            'category_slug' => ['string'],
            'category_slug.*' => ['string', 'exists:categories,slug'],
            'tag_slug' => ['string'],
            'tag_slug.*' => ['string', 'exists:tags,slug'],
            'author_id' => ['integer', 'exists:users,id'],
            'created_by' => ['integer', 'exists:users,id'],
            'published_after' => ['date'],
            'published_before' => ['date'],
            'sort_by' => [Rule::in(['title', 'published_at', 'created_at', 'updated_at'])],
            'sort_direction' => [Rule::in(['asc', 'desc'])],
        ];
    }

    /**
     * Get the default values for missing parameters
     *
     * @return array<string, mixed>
     */
    public function withDefaults(): array
    {
        return array_merge([
            'page' => 1,
            'per_page' => 15,
            'sort_by' => 'published_at',
            'sort_direction' => 'desc',
            'status' => ArticleStatus::PUBLISHED->value,
        ], $this->validated());
    }
}
