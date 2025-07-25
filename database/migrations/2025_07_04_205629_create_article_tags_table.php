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
        Schema::create('article_tags', function (Blueprint $table) {
            $table->foreignId('article_id')
                ->constrained('articles')
                ->onDelete('cascade')
                ->index()
                ->name('article_tags_article_id_foreign');
            $table->foreignId('tag_id')
                ->constrained('tags')
                ->onDelete('cascade')
                ->index()
                ->name('article_tags_tag_id_foreign');
            $table->primary(['article_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_tags');
    }
};
