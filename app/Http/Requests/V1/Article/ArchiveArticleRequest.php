<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Article;

final class ArchiveArticleRequest extends ArticleActionRequest
{
    public function authorize(): bool
    {
        return $this->canPerformAction('edit_others_posts', 'archive_posts');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // No additional validation rules needed for archiving
        ];
    }
}
