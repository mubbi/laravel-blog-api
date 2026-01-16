<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Notification;

use App\Enums\NotificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property-read string|null $search
 * @property-read string|null $type
 * @property-read string|null $status
 * @property-read string|null $created_at_from
 * @property-read string|null $created_at_to
 * @property-read string|null $sort_by
 * @property-read string|null $sort_order
 * @property-read int|null $per_page
 */
final class GetNotificationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasPermission('view_notifications');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::enum(NotificationType::class)],
            'status' => ['nullable', 'string', 'in:verified,unverified'],
            'created_at_from' => ['nullable', 'date'],
            'created_at_to' => ['nullable', 'date', 'after_or_equal:created_at_from'],
            'sort_by' => ['nullable', 'string', 'in:created_at,updated_at,type'],
            'sort_order' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
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
            'sort_by' => 'created_at',
            'sort_order' => 'desc',
            'per_page' => 15,
        ], $this->validated());
    }
}
