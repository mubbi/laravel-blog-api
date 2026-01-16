<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Category;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @property-read bool|null $delete_children
 */
final class DeleteCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        $category = $this->route('category');
        if (! $category instanceof Category) {
            return false;
        }

        return $user->hasPermission('delete_categories');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'delete_children' => ['nullable', 'boolean'],
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
            'delete_children' => false,
        ], $this->validated());
    }
}
