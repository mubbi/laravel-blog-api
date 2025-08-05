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
            $table->text('admin_note')->nullable()->after('moderator_notes');
            $table->text('deleted_reason')->nullable()->after('admin_note');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->after('deleted_reason');
            $table->timestamp('deleted_at')->nullable()->after('deleted_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['deleted_by']);
            $table->dropColumn([
                'admin_note',
                'deleted_reason',
                'deleted_by',
                'deleted_at',
            ]);
        });
    }
};
