<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\User;

use Illuminate\Foundation\Http\FormRequest;

final class GetMeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasPermission('view_own_profile');
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
