<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update enum values to match ArticleAuthorRole enum
        // MySQL doesn't support direct enum alteration, so we use MODIFY COLUMN
        DB::statement("ALTER TABLE article_authors MODIFY COLUMN role ENUM('main', 'co_author', 'contributor') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE article_authors MODIFY COLUMN role ENUM('primary', 'co-author', 'editor') NULL");
    }
};
