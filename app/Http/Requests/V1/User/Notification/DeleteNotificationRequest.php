<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\User\Notification;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request for deleting a user notification
 */
final class DeleteNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        assert($user !== null);

        // Users with manage_notifications permission can delete any notification
        if ($user->hasPermission('manage_notifications')) {
            return true;
        }

        // Users with delete_notifications permission can delete their own notifications
        return $user->hasPermission('delete_notifications');
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
