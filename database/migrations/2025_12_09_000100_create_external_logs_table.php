<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 64);
            $table->string('source', 128)->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('occurred_at');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_logs');
    }
};
