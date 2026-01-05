<?php

declare(strict_types=1);

namespace App\Events\Article;

use App\Models\Article;
use App\Models\ArticleLike;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ArticleDislikedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Article $article,
        public readonly ArticleLike $dislike
    ) {}
}
