<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Comment;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property-read string|null $reason
 */
final class ReportCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only authenticated users can report comments
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
            'reason' => ['nullable', 'string', 'max:1000'],
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
