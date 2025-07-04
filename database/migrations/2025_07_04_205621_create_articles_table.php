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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title')->index();
            $table->string('subtitle')->nullable();
            $table->string('excerpt', 500)->nullable();

            $table->text('content_markdown');
            $table->longText('content_html')->nullable();

            $table->string('featured_image')->nullable();

            $table->enum('status', ['draft', 'published', 'scheduled', 'archived'])->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();

            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();

            $table->foreignId('created_by')
                ->constrained('users')
                ->onDelete('cascade')
                ->index()
                ->comment('FK to users (creator)')
                ->name('articles_created_by_foreign');
            $table->foreignId('approved_by')
                ->constrained('users')
                ->onDelete('cascade')
                ->index()
                ->comment('FK to users (approver)')
                ->name('articles_approved_by_foreign');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['status', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
