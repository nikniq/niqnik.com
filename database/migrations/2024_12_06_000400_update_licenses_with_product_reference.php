<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            if (! Schema::hasColumn('licenses', 'product_id')) {
                $table->unsignedBigInteger('product_id');
            }

            if (Schema::hasColumn('licenses', 'name')) {
                $table->dropColumn('name');
            }

            if (Schema::hasColumn('licenses', 'product_code')) {
                $table->dropColumn('product_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            if (! Schema::hasColumn('licenses', 'name')) {
                $table->string('name');
            }

            if (! Schema::hasColumn('licenses', 'product_code')) {
                $table->string('product_code');
            }

            if (Schema::hasColumn('licenses', 'product_id')) {
                $table->dropColumn('product_id');
            }
        });
    }
};
