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
        Schema::create('course_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('semester'); // mis. "Genap 2223"
            $table->string('no_induk');
            $table->string('nama');
            $table->decimal('na', 5, 2); // nilai angka 0-100
            $table->string('nh'); // nilai huruf: A, B+, B, ...
            $table->decimal('grade_point', 3, 2); // bobot hasil konversi NH
            $table->timestamps();

            $table->index(['course_id', 'no_induk']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_grades');
    }
};
