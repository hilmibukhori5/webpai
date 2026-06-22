<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('payment_status')->default('unpaid')->after('prodi');
            $table->string('bukti_pembayaran_path')->nullable()->after('payment_status');
            $table->string('formulir_terisi_path')->nullable()->after('bukti_pembayaran_path');
            $table->timestamp('decision_sent_at')->nullable()->after('formulir_terisi_path');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'bukti_pembayaran_path', 'formulir_terisi_path', 'decision_sent_at']);
        });
    }
};
