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
        Schema::create('course_thresholds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('percentile', 5, 2); // mis. 25.00 (persentil yang dipakai saat hitung)
            $table->decimal('threshold_na', 5, 2); // batas_bawah NA hasil PERCENTILE.INC
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_thresholds');
    }
};
