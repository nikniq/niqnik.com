<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->string('payment_provider', 32)->nullable()->after('identifier');
            $table->string('payment_reference', 191)->nullable()->after('payment_provider');
            $table->unique(['payment_provider', 'payment_reference']);
        });
    }

    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropUnique(['payment_provider', 'payment_reference']);
            $table->dropColumn(['payment_provider', 'payment_reference']);
        });
    }
};
