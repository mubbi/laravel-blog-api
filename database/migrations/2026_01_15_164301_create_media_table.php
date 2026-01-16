<?php

declare(strict_types=1);

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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type');
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('url')->nullable();
            $table->unsignedBigInteger('size')->comment('File size in bytes');
            $table->string('type')->default('image')->comment('image, video, document, etc.');
            $table->text('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable()->comment('Additional metadata like dimensions, duration, etc.');
            $table->foreignId('uploaded_by')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('FK to users (uploader)')
                ->name('media_uploaded_by_foreign');
            $table->timestamps();

            // Indexes for common query patterns
            // Filter by type (single field filter)
            $table->index(['type']);
            // Filter by type and sort by created_at (most common query pattern)
            $table->index(['type', 'created_at']);
            // Filter by uploaded_by and sort by created_at (user's media library - most common for non-admins)
            $table->index(['uploaded_by', 'created_at']);
            // Filter by uploaded_by and type, sort by created_at (user's media filtered by type)
            $table->index(['uploaded_by', 'type', 'created_at']);
            // Sort by created_at (default sorting for all queries)
            $table->index(['created_at']);
            // Sort by updated_at
            $table->index(['updated_at']);
            // Sort by name (used in sort_by parameter)
            $table->index(['name']);
            // Sort by file_name (used in sort_by parameter)
            $table->index(['file_name']);
            // Sort by size (used in sort_by parameter)
            $table->index(['size']);
        });

        // Article-Media pivot table (many-to-many relationship)
        Schema::create('article_media', function (Blueprint $table) {
            $table->foreignId('article_id')
                ->constrained('articles')
                ->onDelete('cascade')
                ->index()
                ->name('article_media_article_id_foreign');
            $table->foreignId('media_id')
                ->constrained('media')
                ->onDelete('cascade')
                ->index()
                ->name('article_media_media_id_foreign');
            $table->string('usage_type')->default('content')->index()->comment('content, gallery, attachment, etc.');
            $table->integer('order')->default(0)->index();
            $table->timestamps();
            $table->primary(['article_id', 'media_id']);
        });

        // Add foreign key constraint for featured_media_id in articles table
        Schema::table('articles', function (Blueprint $table) {
            $table->foreign('featured_media_id')
                ->references('id')
                ->on('media')
                ->nullOnDelete()
                ->name('articles_featured_media_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_media');
        Schema::dropIfExists('media');
    }
};
