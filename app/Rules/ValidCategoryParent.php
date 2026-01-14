<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Category;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a category parent_id is valid and doesn't create circular references
 */
final class ValidCategoryParent implements ValidationRule
{
    public function __construct(
        private readonly Category $category
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        $parentId = (int) $value;

        // Check if trying to set itself as parent
        if ($parentId === $this->category->id) {
            $fail('The category cannot be its own parent.');

            return;
        }

        $parent = Category::find($parentId);
        if ($parent === null) {
            $fail('The selected parent category does not exist.');

            return;
        }

        // Prevent circular reference - check if parent is a descendant
        $descendants = $this->category->getDescendantIds();
        if (in_array($parentId, $descendants, true)) {
            $fail('The selected parent category cannot be a descendant of this category.');
        }
    }
}
