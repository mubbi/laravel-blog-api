<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property-read int $page
 * @property-read int $per_page
 * @property-read string $sort_by
 * @property-read string $sort_direction
 */
final class GetUserFollowersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint, but requires authentication for private profiles
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
            'sort_by' => [Rule::in(['name', 'created_at', 'updated_at'])],
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
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ], $this->validated());
    }
}
