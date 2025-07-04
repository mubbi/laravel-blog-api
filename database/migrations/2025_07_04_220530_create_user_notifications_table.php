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
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('notification_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->boolean('is_read')->default(false)->index();
            $table->timestamps();

            $table->foreign('notification_id', 'user_notifications_notification_id_foreign')
                ->references('id')
                ->on('notifications')
                ->onDelete('cascade');

            $table->foreign('user_id', 'user_notifications_user_id_foreign')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
