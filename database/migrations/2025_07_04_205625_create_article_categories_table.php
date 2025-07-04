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
        Schema::create('article_categories', function (Blueprint $table) {
            $table->foreignId('article_id')
                ->constrained('articles')
                ->onDelete('cascade')
                ->index()
                ->name('article_categories_article_id_foreign');
            $table->foreignId('category_id')
                ->constrained('categories')
                ->onDelete('cascade')
                ->index()
                ->name('article_categories_category_id_foreign');
            $table->primary(['article_id', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_categories');
    }
};
