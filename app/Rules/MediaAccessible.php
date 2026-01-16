<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Media;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that the user can access the specified media
 * (either owns it or has manage_media permission)
 */
final class MediaAccessible implements ValidationRule
{
    public function __construct(
        private readonly User $user
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

        $mediaId = (int) $value;

        // Users with manage_media permission can use any media
        if ($this->user->hasPermission('manage_media')) {
            return;
        }

        // Check if media exists and user owns it
        $media = Media::find($mediaId);
        if ($media === null) {
            $fail('The selected media does not exist.');

            return;
        }

        // User can only use their own media
        if ($media->uploaded_by !== $this->user->id) {
            $fail('You can only use media that you have uploaded.');
        }
    }
}
