<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Article;

use Illuminate\Foundation\Http\FormRequest;

class GetCommentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'per_page' => 'integer|min:1|max:100',
            'page' => 'integer|min:1',
            'parent_id' => 'nullable|integer|exists:comments,id',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function withDefaults(): array
    {
        return [
            'per_page' => $this->input('per_page', 10),
            'page' => $this->input('page', 1),
            'parent_id' => $this->input('parent_id'),
        ];
    }
}
