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
        Schema::create('article_authors', function (Blueprint $table) {
            $table->foreignId('article_id')
                ->constrained('articles')
                ->onDelete('cascade')
                ->index()
                ->name('article_authors_article_id_foreign');
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->index()
                ->name('article_authors_user_id_foreign');
            $table->enum('role', ['primary', 'co-author', 'editor'])->nullable();
            $table->primary(['article_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_authors');
    }
};
