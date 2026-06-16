<?php

namespace App\Services;

/**
 * Hasil evaluasi eligibility 1 mahasiswa x 1 modul PAI (docs/spec.md bagian 4).
 *
 * eligible_baru / eligible_lama adalah sinyal mentah (apakah masing-masing
 * kriteria 4a/4b lolos secara matematis), sedangkan `decision` adalah hasil
 * akhir SETELAH lewat decision tree 4c (bisa "none" walau eligible_lama true,
 * lihat catatan di spec). UI mahasiswa pakai `decision` untuk 3-state badge;
 * `componentGrades` buat ditampilkan ke admin sebagai rincian nilai.
 */
final class EligibilityResult
{
    public function __construct(
        public readonly bool $eligibleBaru,
        public readonly bool $eligibleLama,
        public readonly string $decision, // baru|lama|none
        public readonly ?int $price,
        public readonly array $componentGrades,
        public readonly string $reason,
    ) {}

    public static function notEligible(string $reason, array $componentGrades = []): self
    {
        return new self(
            eligibleBaru: false,
            eligibleLama: false,
            decision: 'none',
            price: null,
            componentGrades: $componentGrades,
            reason: $reason,
        );
    }
}
