<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the enum to include new values
        DB::statement("ALTER TABLE articles MODIFY COLUMN status ENUM('draft', 'review', 'scheduled', 'published', 'archived', 'trashed') DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE articles MODIFY COLUMN status ENUM('draft', 'scheduled', 'published', 'archived') DEFAULT 'draft'");
    }
};
