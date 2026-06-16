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
        Schema::table('submissions', function (Blueprint $table) {
            // Diisi mahasiswa setelah submission disetujui admin (di luar 8 fase
            // asli spec, ditambah belakangan): upload bukti bayar + formulir
            // permohonan penyetaraan ujian yang sudah dilampirkan di email
            // ApprovedModule. Path relatif ke disk "public" (storage/app/public).
            $table->string('bukti_pembayaran_path')->nullable()->after('rejection_reason');
            $table->string('formulir_terisi_path')->nullable()->after('bukti_pembayaran_path');
            // unpaid|paid -- otomatis jadi "paid" begitu KEDUA path di atas terisi
            // (dikonfirmasi user: tanpa langkah verifikasi admin tambahan).
            $table->string('payment_status')->default('unpaid')->after('formulir_terisi_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn(['bukti_pembayaran_path', 'formulir_terisi_path', 'payment_status']);
        });
    }
};
