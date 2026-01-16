<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Comment;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property-read string|null $reason
 */
final class DeleteCommentRequest extends FormRequest
{
    /**
     * Allow authenticated users - authorization checked in controller using policy
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
