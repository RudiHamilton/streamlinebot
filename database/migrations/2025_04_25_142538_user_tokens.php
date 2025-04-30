<?php

use Carbon\Carbon;
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
        Schema::create('discord_user_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->string('discord_id');
            $table->string('bot_access_token')->unique()->nullable();
            $table->string('spotify_auth_token')->nullable();
            $table->string('spotify_app_token')->nullable();
            $table->string('spotify_app_refresh_token')->nullable();
            $table->datetime('spotify_expires_at')->nullable();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discord_user_access_tokens');
    }
};
