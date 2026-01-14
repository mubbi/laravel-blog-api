<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Admin\Tag;

use App\Models\Tag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property-read string|null $name
 * @property-read string|null $slug
 */
final class UpdateTagRequest extends FormRequest
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

        return $user->hasPermission('edit_tags');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tag = $this->route('tag');
        assert($tag instanceof Tag);

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('tags', 'name')->ignore($tag->id)],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('tags', 'slug')->ignore($tag->id)],
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
