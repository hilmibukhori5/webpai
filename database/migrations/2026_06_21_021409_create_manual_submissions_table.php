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
        Schema::create('manual_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('no_induk', 50);
            $table->string('nama')->nullable();
            $table->foreignId('pai_module_id')->constrained()->cascadeOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['no_induk', 'pai_module_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_submissions');
    }
};
