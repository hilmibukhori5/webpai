<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_upload_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('period', 20); // e.g., "Genap 2324"
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_upload_statuses');
    }
};
