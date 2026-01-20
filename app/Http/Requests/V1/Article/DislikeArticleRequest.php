<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Article;

use Illuminate\Foundation\Http\FormRequest;

final class DislikeArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Public endpoint, but check permission if user is authenticated
        $user = $this->user();

        if ($user === null) {
            return true; // Allow public access (tracked by IP)
        }

        return $user->hasPermission('dislike_posts');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
