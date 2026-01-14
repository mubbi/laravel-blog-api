<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds missing indexes for frequently queried columns to improve query performance.
     */
    public function up(): void
    {
        // Articles table - add missing indexes
        Schema::table('articles', function (Blueprint $table) {
            // Index for created_by lookups
            if (! $this->hasIndex('articles', 'articles_created_by_idx')) {
                $table->index('created_by', 'articles_created_by_idx');
            }

            // Index for approved_by lookups
            if (! $this->hasIndex('articles', 'articles_approved_by_idx')) {
                $table->index('approved_by', 'articles_approved_by_idx');
            }

            // Index for updated_by lookups
            if (! $this->hasIndex('articles', 'articles_updated_by_idx')) {
                $table->index('updated_by', 'articles_updated_by_idx');
            }

            // Index for status filtering
            if (! $this->hasIndex('articles', 'articles_status_idx')) {
                $table->index('status', 'articles_status_idx');
            }

            // Index for published_at filtering
            if (! $this->hasIndex('articles', 'articles_published_at_idx')) {
                $table->index('published_at', 'articles_published_at_idx');
            }

            // Index for is_featured filtering
            if (! $this->hasIndex('articles', 'articles_is_featured_idx')) {
                $table->index('is_featured', 'articles_is_featured_idx');
            }

            // Index for is_pinned filtering
            if (! $this->hasIndex('articles', 'articles_is_pinned_idx')) {
                $table->index('is_pinned', 'articles_is_pinned_idx');
            }

            // Index for report_count filtering
            if (! $this->hasIndex('articles', 'articles_report_count_idx')) {
                $table->index('report_count', 'articles_report_count_idx');
            }

            // Index for last_reported_at filtering
            if (! $this->hasIndex('articles', 'articles_last_reported_at_idx')) {
                $table->index('last_reported_at', 'articles_last_reported_at_idx');
            }

            // Composite index for filtering by status and featured status together
            if (! $this->hasIndex('articles', 'articles_status_featured_published_idx')) {
                $table->index(['status', 'is_featured', 'published_at'], 'articles_status_featured_published_idx');
            }
        });

        // Comments table - add missing indexes
        Schema::table('comments', function (Blueprint $table) {
            // Index for user_id lookups
            if (! $this->hasIndex('comments', 'comments_user_id_idx')) {
                $table->index('user_id', 'comments_user_id_idx');
            }

            // Index for article_id lookups
            if (! $this->hasIndex('comments', 'comments_article_id_idx')) {
                $table->index('article_id', 'comments_article_id_idx');
            }

            // Index for parent_comment_id lookups
            if (! $this->hasIndex('comments', 'comments_parent_comment_id_idx')) {
                $table->index('parent_comment_id', 'comments_parent_comment_id_idx');
            }

            // Index for status filtering
            if (! $this->hasIndex('comments', 'comments_status_idx')) {
                $table->index('status', 'comments_status_idx');
            }

            // Index for finding replies to a specific comment
            if (! $this->hasIndex('comments', 'comments_parent_comment_created_idx')) {
                $table->index(['parent_comment_id', 'created_at'], 'comments_parent_comment_created_idx');
            }
        });

        // Users table - add missing indexes
        Schema::table('users', function (Blueprint $table) {
            // Index for email lookups (already unique, but ensure index exists)
            // Unique constraint already creates an index

            // Index for is_verified filtering (if exists)
            if ($this->columnExists('users', 'is_verified') && ! $this->hasIndex('users', 'users_is_verified_idx')) {
                $table->index('is_verified', 'users_is_verified_idx');
            }

            // Index for banned_at filtering
            if (! $this->hasIndex('users', 'users_banned_at_idx')) {
                $table->index('banned_at', 'users_banned_at_idx');
            }

            // Index for blocked_at filtering
            if (! $this->hasIndex('users', 'users_blocked_at_idx')) {
                $table->index('blocked_at', 'users_blocked_at_idx');
            }
        });

        // Newsletter subscribers table - add missing indexes
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            // Index for email lookups (already unique, but ensure index exists)
            // Unique constraint already creates an index

            // Index for is_verified filtering
            if (! $this->hasIndex('newsletter_subscribers', 'newsletter_subscribers_is_verified_idx')) {
                $table->index('is_verified', 'newsletter_subscribers_is_verified_idx');
            }

            // Index for subscribed_at filtering
            if (! $this->hasIndex('newsletter_subscribers', 'newsletter_subscribers_subscribed_at_idx')) {
                $table->index('subscribed_at', 'newsletter_subscribers_subscribed_at_idx');
            }

            // Index for verification_token lookups
            if (! $this->hasIndex('newsletter_subscribers', 'newsletter_subscribers_verification_token_idx')) {
                $table->index('verification_token', 'newsletter_subscribers_verification_token_idx');
            }

            // Index for verification_token_expires_at filtering
            if (! $this->hasIndex('newsletter_subscribers', 'newsletter_subscribers_verification_token_expires_at_idx')) {
                $table->index('verification_token_expires_at', 'newsletter_subscribers_verification_token_expires_at_idx');
            }
        });

        // Notifications table - add missing indexes
        Schema::table('notifications', function (Blueprint $table) {
            // Index for type filtering
            if (! $this->hasIndex('notifications', 'notifications_type_idx')) {
                $table->index('type', 'notifications_type_idx');
            }
        });

        // Notification audiences table - add missing indexes
        if (Schema::hasTable('notification_audiences')) {
            Schema::table('notification_audiences', function (Blueprint $table) {
                // Index for audience_type filtering
                if (! $this->hasIndex('notification_audiences', 'notification_audiences_audience_type_idx')) {
                    $table->index('audience_type', 'notification_audiences_audience_type_idx');
                }

                // Index for audience_id filtering
                if (! $this->hasIndex('notification_audiences', 'notification_audiences_audience_id_idx')) {
                    $table->index('audience_id', 'notification_audiences_audience_id_idx');
                }
            });
        }

        // User notifications table - add missing indexes
        if (Schema::hasTable('user_notifications')) {
            Schema::table('user_notifications', function (Blueprint $table) {
                // Index for is_read filtering
                if ($this->columnExists('user_notifications', 'is_read') && ! $this->hasIndex('user_notifications', 'user_notifications_is_read_idx')) {
                    $table->index('is_read', 'user_notifications_is_read_idx');
                }
            });
        }

        // Article likes table - add index for counting likes/dislikes
        if (Schema::hasTable('article_likes')) {
            Schema::table('article_likes', function (Blueprint $table) {
                // Index for counting likes by article
                if (! $this->hasIndex('article_likes', 'article_likes_article_created_idx')) {
                    $table->index(['article_id', 'created_at'], 'article_likes_article_created_idx');
                }
            });
        }

        // User followers table - add composite index for follower queries
        if (Schema::hasTable('user_followers')) {
            Schema::table('user_followers', function (Blueprint $table) {
                // Index for finding all followers of a user
                if (! $this->hasIndex('user_followers', 'user_followers_following_created_idx')) {
                    $table->index(['following_id', 'created_at'], 'user_followers_following_created_idx');
                }

                // Index for finding all users a user is following
                if (! $this->hasIndex('user_followers', 'user_followers_follower_created_idx')) {
                    $table->index(['follower_id', 'created_at'], 'user_followers_follower_created_idx');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex('articles_created_by_idx');
            $table->dropIndex('articles_approved_by_idx');
            $table->dropIndex('articles_updated_by_idx');
            $table->dropIndex('articles_status_idx');
            $table->dropIndex('articles_published_at_idx');
            $table->dropIndex('articles_is_featured_idx');
            $table->dropIndex('articles_is_pinned_idx');
            $table->dropIndex('articles_report_count_idx');
            $table->dropIndex('articles_last_reported_at_idx');
            $table->dropIndex('articles_status_featured_published_idx');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_user_id_idx');
            $table->dropIndex('comments_article_id_idx');
            $table->dropIndex('comments_parent_comment_id_idx');
            $table->dropIndex('comments_status_idx');
            $table->dropIndex('comments_parent_comment_created_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            if ($this->hasIndex('users', 'users_is_verified_idx')) {
                $table->dropIndex('users_is_verified_idx');
            }
            $table->dropIndex('users_banned_at_idx');
            $table->dropIndex('users_blocked_at_idx');
        });

        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->dropIndex('newsletter_subscribers_is_verified_idx');
            $table->dropIndex('newsletter_subscribers_subscribed_at_idx');
            $table->dropIndex('newsletter_subscribers_verification_token_idx');
            $table->dropIndex('newsletter_subscribers_verification_token_expires_at_idx');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_type_idx');
        });

        if (Schema::hasTable('notification_audiences')) {
            Schema::table('notification_audiences', function (Blueprint $table) {
                $table->dropIndex('notification_audiences_audience_type_idx');
                $table->dropIndex('notification_audiences_audience_id_idx');
            });
        }

        if (Schema::hasTable('user_notifications')) {
            Schema::table('user_notifications', function (Blueprint $table) {
                if ($this->hasIndex('user_notifications', 'user_notifications_is_read_idx')) {
                    $table->dropIndex('user_notifications_is_read_idx');
                }
            });
        }

        if (Schema::hasTable('article_likes')) {
            Schema::table('article_likes', function (Blueprint $table) {
                $table->dropIndex('article_likes_article_created_idx');
            });
        }

        if (Schema::hasTable('user_followers')) {
            Schema::table('user_followers', function (Blueprint $table) {
                $table->dropIndex('user_followers_following_created_idx');
                $table->dropIndex('user_followers_follower_created_idx');
            });
        }
    }

    /**
     * Check if an index exists on a table
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        $tableName = $connection->getTablePrefix().$table;

        $result = $connection->select(
            'SELECT COUNT(*) as count FROM information_schema.statistics 
             WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $tableName, $indexName]
        );

        return isset($result[0]) && ((int) $result[0]->count) > 0;
    }

    /**
     * Check if a column exists on a table
     */
    private function columnExists(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }
};
