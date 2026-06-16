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
        Schema::create('module_course', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pai_module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('curriculum'); // lama|baru
            $table->timestamps();

            // Satu course bisa nempel ke modul yang sama lewat 2 baris (lama & baru).
            $table->unique(['pai_module_id', 'course_id', 'curriculum']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_course');
    }
};
