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
        Schema::create('notification_audiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained('notifications')->onDelete('cascade');
            $table->enum('audience_type', ['all', 'user', 'role', 'category'])->index();
            $table->unsignedBigInteger('audience_id')
                ->nullable()
                ->index()
                ->comment('Null = all users; otherwise store user_id, role_id, or category_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_audiences');
    }
};
