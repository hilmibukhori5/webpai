<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('module_course', function (Blueprint $table) {
            $table->string('prodi', 50)->default('S1 Ilmu Aktuaria')->after('curriculum');
        });
    }

    public function down(): void
    {
        Schema::table('module_course', function (Blueprint $table) {
            $table->dropColumn('prodi');
        });
    }
};
