<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Helper class for exception-related utilities
 */
final class ExceptionHelper
{
    /**
     * Get the appropriate error message for a ModelNotFoundException based on the model type.
     *
     * @param  ModelNotFoundException<\Illuminate\Database\Eloquent\Model>  $e  The ModelNotFoundException instance
     * @return string The localized error message
     */
    public static function getModelNotFoundMessage(ModelNotFoundException $e): string
    {
        $model = class_basename($e->getModel() ?? 'Model');

        return match ($model) {
            'Article' => __('common.article_not_found'),
            'User' => __('common.user_not_found'),
            'Comment' => __('common.comment_not_found'),
            'Category' => __('common.category_not_found'),
            'Tag' => __('common.tag_not_found'),
            'NewsletterSubscriber' => __('common.subscriber_not_found'),
            'Notification' => __('common.notification_not_found'),
            'Role' => __('common.role_not_found'),
            'Permission' => __('common.permission_not_found'),
            default => __('common.not_found'),
        };
    }
}
