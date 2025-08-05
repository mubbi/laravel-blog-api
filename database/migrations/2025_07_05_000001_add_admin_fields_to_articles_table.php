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
        Schema::table('articles', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('status');
            $table->boolean('is_pinned')->default(false)->after('is_featured');
            $table->timestamp('featured_at')->nullable()->after('is_pinned');
            $table->timestamp('pinned_at')->nullable()->after('featured_at');
            $table->integer('report_count')->default(0)->after('pinned_at');
            $table->timestamp('last_reported_at')->nullable()->after('report_count');
            $table->text('report_reason')->nullable()->after('last_reported_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn([
                'is_featured',
                'is_pinned',
                'featured_at',
                'pinned_at',
                'report_count',
                'last_reported_at',
                'report_reason',
            ]);
        });
    }
};
