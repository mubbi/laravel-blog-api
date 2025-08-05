<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Admin\Comment;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property-read string|null $admin_note
 */
final class ApproveCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->can('approve_comments');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'admin_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
