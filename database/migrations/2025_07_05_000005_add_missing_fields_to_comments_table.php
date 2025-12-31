<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->text('admin_note')->nullable()->after('moderator_notes');
            $table->text('deleted_reason')->nullable()->after('admin_note');
            $table->foreignId('deleted_by')
                ->nullable()
                ->constrained('users')
                ->after('deleted_reason')
                ->name('comments_deleted_by_foreign');
            $table->timestamp('deleted_at')->nullable()->after('deleted_by')->index();

            // Composite indexes with deleted_at for soft delete queries
            // Laravel SoftDeletes automatically adds WHERE deleted_at IS NULL to all queries
            // These indexes optimize common query patterns with soft deletes
            // Note: Explicit names are required to stay under MySQL's 64-character identifier limit

            // Article comments queries (most common)
            $table->index(['article_id', 'parent_comment_id', 'deleted_at', 'created_at'], 'comments_article_parent_deleted_created_idx');
            $table->index(['article_id', 'deleted_at', 'created_at'], 'comments_article_deleted_created_idx');
            $table->index(['parent_comment_id', 'deleted_at', 'created_at'], 'comments_parent_deleted_created_idx');

            // User comments queries
            $table->index(['user_id', 'deleted_at', 'created_at'], 'comments_user_deleted_created_idx');

            // Status filtering queries
            $table->index(['article_id', 'status', 'deleted_at'], 'comments_article_status_deleted_idx');
            $table->index(['user_id', 'status', 'deleted_at'], 'comments_user_status_deleted_idx');
            $table->index(['status', 'deleted_at', 'created_at'], 'comments_status_deleted_created_idx');

            // General ordering with soft deletes
            $table->index(['deleted_at', 'created_at'], 'comments_deleted_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['deleted_by']);
            $table->dropColumn([
                'admin_note',
                'deleted_reason',
                'deleted_by',
                'deleted_at',
            ]);
        });
    }
};
