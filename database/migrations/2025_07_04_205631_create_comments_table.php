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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')
                ->constrained('articles')
                ->onDelete('cascade')
                ->name('comments_article_id_foreign');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->name('comments_user_id_foreign');
            $table->text('content');
            $table->foreignId('parent_comment_id')
                ->nullable()
                ->constrained('comments')
                ->onDelete('cascade')
                ->name('comments_parent_comment_id_foreign');
            $table->timestamps();

            // Composite indexes for common query patterns
            $table->index(['article_id', 'parent_comment_id']);
            $table->index(['article_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['created_at']);

            // Full-text index for content search
            $table->fullText(['content']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
