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
        Schema::table('comments', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected', 'spam'])->default('pending')->after('content');
            $table->timestamp('approved_at')->nullable()->after('status');
            $table->foreignId('approved_by')->nullable()->constrained('users')->after('approved_at');
            $table->integer('report_count')->default(0)->after('approved_by');
            $table->timestamp('last_reported_at')->nullable()->after('report_count');
            $table->text('report_reason')->nullable()->after('last_reported_at');
            $table->text('moderator_notes')->nullable()->after('report_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'status',
                'approved_at',
                'approved_by',
                'report_count',
                'last_reported_at',
                'report_reason',
                'moderator_notes',
            ]);
        });
    }
};
