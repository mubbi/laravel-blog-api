<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Category;

use Illuminate\Foundation\Http\FormRequest;

final class GetCategoriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Public endpoint, but check permission if user is authenticated
        $user = $this->user();

        if ($user === null) {
            return true; // Allow public access
        }

        return $user->hasPermission('view_categories');
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

    /**
     * Get the default values for missing parameters
     *
     * @return array<string, mixed>
     */
    public function withDefaults(): array
    {
        return [];
    }
}
