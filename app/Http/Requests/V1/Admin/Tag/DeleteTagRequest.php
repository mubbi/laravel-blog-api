<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Admin\Tag;

use App\Models\Tag;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        $tag = $this->route('tag');
        if (! $tag instanceof Tag) {
            return false;
        }

        return $user->hasPermission('delete_tags');
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
        return $this->validated();
    }
}
