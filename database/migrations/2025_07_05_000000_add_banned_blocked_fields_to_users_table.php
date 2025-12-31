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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('banned_at')->nullable()->after('website')->index();
            $table->timestamp('blocked_at')->nullable()->after('banned_at')->index();

            // Composite indexes for status filtering
            $table->index(['banned_at', 'blocked_at']);
            $table->index(['banned_at', 'created_at']);
            $table->index(['blocked_at', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['banned_at', 'blocked_at']);
        });
    }
};
