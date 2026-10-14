<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A downstream game registered with the portal as an SSO client.
        Schema::create('game_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('product_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('homepage_url', 2048)->nullable();
            $table->string('client_id', 64)->unique();
            $table->string('client_secret'); // hashed
            $table->json('redirect_uris');
            $table->boolean('is_public')->default(false); // PKCE-only, no secret (static JS games)
            $table->boolean('is_enabled')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // One-time authorization codes handed to the game via redirect.
        Schema::create('sso_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code_hash', 64)->unique();
            $table->string('redirect_uri', 2048);
            $table->string('code_challenge', 128)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        // Long-lived bearer tokens a game holds for a signed-in player.
        Schema::create('player_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'game_client_id']);
        });

        // Raw events reported by games.
        Schema::create('game_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('event', 64);
            $table->bigInteger('score')->nullable();
            $table->string('level', 64)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->boolean('won')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->nullable();

            $table->index(['game_client_id', 'user_id', 'occurred_at']);
            $table->index(['game_client_id', 'event']);
        });

        // Per-player, per-game aggregates kept current as events arrive.
        Schema::create('player_game_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('events')->default(0);
            $table->unsignedInteger('plays')->default(0);
            $table->unsignedInteger('wins')->default(0);
            $table->bigInteger('total_score')->default(0);
            $table->bigInteger('best_score')->nullable();
            $table->unsignedBigInteger('total_duration_seconds')->default(0);
            $table->string('last_level', 64)->nullable();
            $table->timestamp('first_played_at')->nullable();
            $table->timestamp('last_played_at')->nullable();
            $table->timestamps();

            $table->unique(['game_client_id', 'user_id']);
            $table->index(['game_client_id', 'best_score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_game_stats');
        Schema::dropIfExists('game_events');
        Schema::dropIfExists('player_tokens');
        Schema::dropIfExists('sso_codes');
        Schema::dropIfExists('game_clients');
    }
};
