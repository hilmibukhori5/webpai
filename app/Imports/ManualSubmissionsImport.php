<?php

namespace App\Imports;

use App\Models\ManualSubmission;
use App\Models\PaiModule;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

/**
 * Import daftar pengajuan manual (sebelum sistem web) dari Excel.
 *
 * Format Excel yang didukung:
 *   Kolom A: NO (nomor urut, bisa kosong di baris lanjutan)
 *   Kolom B: NIM (kosong di baris lanjutan — ikut NIM baris di atasnya)
 *   Kolom C: NAMA
 *   Kolom D: MODUL PAI YANG DISETUJUI (mis. "A30 EKONOMI", "A10 Matematika Keuangan")
 *
 * Baris header (baris pertama) dilewati secara otomatis.
 */
class ManualSubmissionsImport implements ToCollection
{
    private int $imported = 0;

    private int $skipped = 0;

    private ?string $note;

    public function __construct(?string $note = null)
    {
        $this->note = $note;
    }

    public function collection(Collection $rows): void
    {
        // Preload semua modul sekali
        $modulesByCode = PaiModule::all()->keyBy('code');

        $currentNim = null;
        $currentNama = null;

        foreach ($rows as $index => $row) {
            // Lewati baris header
            if ($index === 0) {
                continue;
            }

            $nim = trim((string) ($row[1] ?? ''));
            $nama = trim((string) ($row[2] ?? ''));
            $moduleText = trim((string) ($row[3] ?? ''));

            // Update current NIM/NAMA kalau baris ini punya NIM
            if ($nim !== '') {
                $currentNim = $nim;
                $currentNama = $nama !== '' ? $nama : null;
            }

            if (! $currentNim || $moduleText === '') {
                continue;
            }

            // Ekstrak kode modul: "A30 EKONOMI" → "A30"
            if (! preg_match('/^(A[1-7]0)/i', $moduleText, $matches)) {
                $this->skipped++;
                continue;
            }

            $moduleCode = strtoupper($matches[1]);
            $paiModule = $modulesByCode->get($moduleCode);

            if (! $paiModule) {
                $this->skipped++;
                continue;
            }

            ManualSubmission::updateOrCreate(
                ['no_induk' => $currentNim, 'pai_module_id' => $paiModule->id],
                ['nama' => $currentNama, 'note' => $this->note],
            );

            $this->imported++;
        }
    }

    public function importedCount(): int
    {
        return $this->imported;
    }

    public function skippedCount(): int
    {
        return $this->skipped;
    }
}
