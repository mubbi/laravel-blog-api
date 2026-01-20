<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Notification;

use App\Enums\NotificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property-read string $type
 * @property-read array<string, mixed> $message
 * @property-read array<string> $audiences
 * @property-read array<int>|null $user_ids
 */
final class CreateNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasPermission('send_notifications');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(NotificationType::class)],
            'message' => ['required', 'array'],
            'message.title' => ['required', 'string', 'max:255'],
            'message.body' => ['required', 'string', 'max:255'],
            'message.priority' => ['required', 'string', 'max:255'],
            'audiences' => ['required', 'array', 'min:1'],
            'audiences.*' => ['string', 'in:all_users,administrators,specific_users'],
            'user_ids' => ['required_if:audiences,specific_users', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => __('validation.custom.type.required'),
            'message.required' => __('validation.custom.message.required'),
            'message.title.required' => __('validation.custom.message.title.required'),
            'audiences.required' => __('validation.custom.audiences.required'),
            'audiences.min' => __('validation.custom.audiences.min'),
            'user_ids.required_if' => __('validation.custom.user_ids.required_if'),
        ];
    }
}
