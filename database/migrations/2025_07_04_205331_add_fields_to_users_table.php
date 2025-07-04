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
            $table->string('avatar_url')->nullable()->after('password');
            $table->text('bio')->nullable()->after('avatar_url');

            // Social links/usernames
            $table->string('twitter')->nullable()->after('bio');
            $table->string('facebook')->nullable()->after('twitter');
            $table->string('linkedin')->nullable()->after('facebook');
            $table->string('github')->nullable()->after('linkedin');
            $table->string('website')->nullable()->after('github');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'avatar_url',
                'bio',
                'twitter',
                'facebook',
                'linkedin',
                'github',
                'website']);
        });
    }
};
