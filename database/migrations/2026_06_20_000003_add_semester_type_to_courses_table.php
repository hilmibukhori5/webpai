<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('semester_type', 10)->default('Genap')->after('sks');
        });

        // Inferensi dari kode matkul: MAA61xxx = Ganjil, MAA62xxx = Genap
        DB::statement("UPDATE courses SET semester_type = 'Ganjil' WHERE code LIKE 'MAA61%'");
        DB::statement("UPDATE courses SET semester_type = 'Genap'  WHERE code LIKE 'MAA62%'");
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('semester_type');
        });
    }
};
