<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('licenses', 'seats_used')) {
            return;
        }

        Schema::table('licenses', function (Blueprint $table) {
            $table->dropColumn('seats_used');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('licenses', 'seats_used')) {
            return;
        }

        Schema::table('licenses', function (Blueprint $table) {
            $table->unsignedInteger('seats_used')->default(0)->after('seats_total');
        });
    }
};
