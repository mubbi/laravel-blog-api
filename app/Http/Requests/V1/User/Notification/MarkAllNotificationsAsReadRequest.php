<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\User\Notification;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request for marking all notifications as read
 */
final class MarkAllNotificationsAsReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        assert($user !== null);

        // Users must have read_notifications permission to mark notifications as read
        return $user->hasPermission('read_notifications');
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
