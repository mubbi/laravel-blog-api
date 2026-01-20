<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Newsletter;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property-read string $email
 */
final class UnsubscribeRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Public endpoint with email verification, but check permission if user is authenticated
        $user = $this->user();

        if ($user === null) {
            return true; // Allow public access with email verification
        }

        return $user->hasPermission('unsubscribe_newsletter');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
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
