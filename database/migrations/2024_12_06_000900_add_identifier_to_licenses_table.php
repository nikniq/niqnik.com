<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            if (!Schema::hasColumn('licenses', 'identifier')) {
                $table->string('identifier')->unique()->after('id');
            }
        });

        DB::table('licenses')
            ->where(function ($query) {
                $query->whereNull('identifier')->orWhere('identifier', '=','');
            })
            ->orderBy('id')
            ->lazyById()
            ->each(function ($license) {
            $identifier = null;
            do {
                $identifier = strtoupper(Str::random(4).'-'.Str::random(4).'-'.Str::random(4));
            } while (DB::table('licenses')->where('identifier', $identifier)->exists());

            DB::table('licenses')
                ->where('id', $license->id)
                ->update(['identifier' => $identifier]);
            });
    }

    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            if (Schema::hasColumn('licenses', 'identifier')) {
                $table->dropUnique('licenses_identifier_unique');
                $table->dropColumn('identifier');
            }
        });
    }
};
