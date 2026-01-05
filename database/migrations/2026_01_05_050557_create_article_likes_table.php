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
        Schema::create('article_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')
                ->constrained('articles')
                ->onDelete('cascade')
                ->index()
                ->name('article_likes_article_id_foreign');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->index()
                ->name('article_likes_user_id_foreign');
            $table->string('ip_address', 45)->nullable()->index()->comment('IPv4 or IPv6 address for anonymous likes');
            $table->enum('type', ['like', 'dislike'])->index();
            $table->timestamps();

            // Composite unique index: ensures one like/dislike per article per user OR per IP
            // Application logic ensures: if user_id is set, ip_address is NULL, and vice versa
            $table->unique(['article_id', 'user_id', 'ip_address'], 'article_likes_article_user_ip_unique');

            // Index for counting likes/dislikes per article
            $table->index(['article_id', 'type'], 'article_likes_article_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_likes');
    }
};
