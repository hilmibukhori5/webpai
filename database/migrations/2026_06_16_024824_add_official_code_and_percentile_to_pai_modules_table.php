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
        Schema::table('pai_modules', function (Blueprint $table) {
            // CF1-CF4, TA1-TA3 — referensi nama resmi ASAI (bagian 2 spec). code (A10-A70)
            // tetap dipakai sebagai identifier internal & di UI.
            $table->string('official_code')->nullable()->unique()->after('code');

            // P pada PERCENTILE.INC, beda per modul (bagian 2 & 4a spec), bukan satu nilai global.
            $table->unsignedTinyInteger('percentile')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pai_modules', function (Blueprint $table) {
            $table->dropColumn(['official_code', 'percentile']);
        });
    }
};
