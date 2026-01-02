<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Admin\Article;

use App\Http\Requests\V1\Article\ArticleActionRequest;

final class ArchiveArticleRequest extends ArticleActionRequest
{
    public function authorize(): bool
    {
        return $this->canPerformAction('edit_others_posts', 'edit_posts');
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
