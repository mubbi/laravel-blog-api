<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Admin\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property-read int $page
 * @property-read int $per_page
 * @property-read string|null $search
 * @property-read int|null $role_id
 * @property-read string|null $status
 * @property-read string|null $created_after
 * @property-read string|null $created_before
 * @property-read string $sort_by
 * @property-read string $sort_direction
 */
final class GetUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasPermission('view_users');
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
            'role_id' => ['integer', 'exists:roles,id'],
            'status' => [Rule::in(['active', 'banned', 'blocked'])],
            'created_after' => ['date'],
            'created_before' => ['date'],
            'sort_by' => [Rule::in(['name', 'email', 'created_at', 'updated_at'])],
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
