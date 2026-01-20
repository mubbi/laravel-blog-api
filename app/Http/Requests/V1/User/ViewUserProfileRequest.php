<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\User;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property-read int $user_id
 */
final class ViewUserProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Public endpoint, but check permission if user is authenticated
        $user = $this->user();

        if ($user === null) {
            return true; // Allow public access
        }

        return $user->hasPermission('view_user_profiles');
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
        return $this->validated();
    }
}
