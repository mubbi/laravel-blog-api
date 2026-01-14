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
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('verification_token', 255)->nullable();
            $table->timestamp('verification_token_expires_at')->nullable();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->name('newsletter_subscribers_user_id_foreign');
            $table->boolean('is_verified')->default(false)->index();
            $table->timestamp('subscribed_at')->useCurrent()->index();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();

            // Composite indexes for common query patterns
            $table->index(['is_verified', 'created_at']);
            $table->index(['created_at']);
            $table->index('verification_token');
            $table->index('verification_token_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
    }
};
