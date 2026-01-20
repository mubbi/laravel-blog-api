<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property-read string $name
 * @property-read string $email
 * @property-read string $password
 * @property-read string|null $avatar_url
 * @property-read string|null $bio
 * @property-read string|null $twitter
 * @property-read string|null $facebook
 * @property-read string|null $linkedin
 * @property-read string|null $github
 * @property-read string|null $website
 * @property-read int|null $role_id
 */
final class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasPermission('create_users');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'avatar_url' => ['nullable', 'url', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'twitter' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'linkedin' => ['nullable', 'string', 'max:255'],
            'github' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
        ];
    }

    /**
     * Get the default values for missing parameters
     *
     * @return array<string, mixed>
     */
    public function withDefaults(): array
    {
        return $this->validated();
    }
}
