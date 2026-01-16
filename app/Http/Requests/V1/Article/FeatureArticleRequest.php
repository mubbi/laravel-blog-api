<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Article;

use Illuminate\Foundation\Http\FormRequest;

final class FeatureArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasPermission('feature_posts');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // No additional validation rules needed for featuring
        ];
    }
}
