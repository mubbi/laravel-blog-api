<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RefreshTokenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            /**
             * Must be valid sanctum refresh token.
             *
             * @var string
             *
             * @example 1|abcdefghijklmnopqrstuvwxyz1234567
             */
            'refresh_token' => ['required', 'string'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'refresh_token.required' => 'The refresh token is required.',
            'refresh_token.string' => 'The refresh token must be a string.',
        ];
    }
}
