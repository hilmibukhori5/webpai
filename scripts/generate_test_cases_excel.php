<?php

/**
 * Generate test-cases-eligibility.xlsx
 * Jalankan: php scripts/generate_test_cases_excel.php
 */

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

// ─── Warna ──────────────────────────────────────────────────────────────────
const C_HEADER_BG   = 'FF1E3A5F'; // biru tua — header
const C_HEADER_FONT = 'FFFFFFFF'; // putih
const C_LAMA_BG     = 'FFdbeafe'; // biru muda — decision lama
const C_LAMA_FONT   = 'FF1e40af';
const C_BARU_BG     = 'FFd1fae5'; // hijau muda — decision baru
const C_BARU_FONT   = 'FF065f46';
const C_NONE_BG     = 'FFfee2e2'; // merah muda — decision none
const C_NONE_FONT   = 'FF991b1b';
const C_SECTION_BG  = 'FFf1f5f9'; // abu abu — section header
const C_YES         = 'FF16a34a'; // hijau teks — Ya
const C_NO          = 'FFdc2626'; // merah teks — Tidak/—
const C_NOTE_BG     = 'FFfffbeb'; // kuning — catatan penting

// ─── Data Test Cases ─────────────────────────────────────────────────────────
// Kolom: no, method, skenario, prodi, modul, kursus1_code, kursus1_nama, kursus1_sks, kursus1_kurikulum,
//        kursus2_code, kursus2_nama, kursus2_sks, kursus2_kurikulum,
//        ta_nilai, nh1, gp1, nh2, gp2, weighted_avg, avg_gt35,
//        na1, threshold1, na1_lulus, na2, threshold2, na2_lulus, na_semua_lulus,
//        force_old, eligible_lama, eligible_baru, decision, biaya, catatan

$testCases = [
    [
        'no'            => 1,
        'method'        => 'test_eligible_baru_when_na_meets_threshold_on_all_components',
        'skenario'      => 'PKS Baru murni — weighted avg gagal, NA lolos persentil',
        'prodi'         => 'S1 Ilmu Aktuaria',
        'modul'         => 'A10',
        'k1_code'       => 'MAA62043',
        'k1_nama'       => 'Matematika Finansial I',
        'k1_sks'        => 3,
        'k1_kur'        => 'Baru',
        'k2_code'       => 'MAA61041',
        'k2_nama'       => 'Matematika Finansial II',
        'k2_sks'        => 3,
        'k2_kur'        => 'Baru',
        'ta'            => 'Genap 2425 / Ganjil 2425',
        'nh1'           => 'C',
        'gp1'           => 2.0,
        'nh2'           => 'C',
        'gp2'           => 2.0,
        'wavg'          => 2.0,
        'avg_gt35'      => 'Tidak',
        'na1'           => 85.0,
        'thr1'          => 80.0,
        'na1_ok'        => 'Ya',
        'na2'           => 90.0,
        'thr2'          => 80.0,
        'na2_ok'        => 'Ya',
        'na_all_ok'     => 'Ya',
        'force_old'     => 'Tidak',
        'eli_lama'      => 'Tidak',
        'eli_baru'      => 'Ya',
        'decision'      => 'baru',
        'biaya'         => 550000,
        'catatan'       => 'NH=C sengaja agar weighted avg=2.0 ≤ 3.5 → eligibleLama=false → PKS Baru terpilih',
    ],
    [
        'no'            => 2,
        'method'        => 'test_lama_prioritized_when_both_curriculum_sets_are_matched',
        'skenario'      => 'Rule a — kedua set (baru+lama) matched → Adendum PKS Lama diutamakan',
        'prodi'         => 'S1 Ilmu Aktuaria',
        'modul'         => 'A10',
        'k1_code'       => 'MAA62043 (baru) + MAA62009 (lama)',
        'k1_nama'       => 'Matematika Finansial I — dua versi',
        'k1_sks'        => 3,
        'k1_kur'        => 'Baru + Lama',
        'k2_code'       => 'MAA61041 (baru) + MAA61015 (lama)',
        'k2_nama'       => 'Matematika Finansial II — dua versi',
        'k2_sks'        => 3,
        'k2_kur'        => 'Baru + Lama',
        'ta'            => 'Genap 2425 / Ganjil 2425 (semua)',
        'nh1'           => 'A',
        'gp1'           => 4.0,
        'nh2'           => 'A',
        'gp2'           => 4.0,
        'wavg'          => 4.0,
        'avg_gt35'      => 'Ya',
        'na1'           => '90 (baru), 85 (lama)',
        'thr1'          => '80 (untuk baru)',
        'na1_ok'        => 'Ya',
        'na2'           => '88 (baru), 82 (lama)',
        'thr2'          => '80 (untuk baru)',
        'na2_ok'        => 'Ya',
        'na_all_ok'     => 'Ya',
        'force_old'     => 'Tidak',
        'eli_lama'      => 'Ya',
        'eli_baru'      => 'Ya',
        'decision'      => 'lama',
        'biaya'         => 500000,
        'catatan'       => 'Rule a: ada set baru DAN lama → keduanya dievaluasi, lama diutamakan karena Rp500.000 < Rp550.000',
    ],
    [
        'no'            => 3,
        'method'        => 'test_none_when_only_lama_curriculum_codes_matched',
        'skenario'      => 'Rule c — HANYA kode kurikulum lama matched → tidak bisa disetarakan',
        'prodi'         => 'S1 Ilmu Aktuaria',
        'modul'         => 'A10',
        'k1_code'       => 'MAA62009',
        'k1_nama'       => 'Matematika Finansial I',
        'k1_sks'        => 3,
        'k1_kur'        => 'Lama',
        'k2_code'       => 'MAA61015',
        'k2_nama'       => 'Matematika Finansial II',
        'k2_sks'        => 3,
        'k2_kur'        => 'Lama',
        'ta'            => 'Genap 2223',
        'nh1'           => 'A',
        'gp1'           => 4.0,
        'nh2'           => 'A',
        'gp2'           => 4.0,
        'wavg'          => 4.0,
        'avg_gt35'      => 'Ya (tidak dievaluasi)',
        'na1'           => 90.0,
        'thr1'          => '—',
        'na1_ok'        => '—',
        'na2'           => 88.0,
        'thr2'          => '—',
        'na2_ok'        => '—',
        'na_all_ok'     => 'Tidak',
        'force_old'     => 'Ya (tidak relevan)',
        'eli_lama'      => 'Tidak',
        'eli_baru'      => 'Tidak',
        'decision'      => 'none',
        'biaya'         => null,
        'catatan'       => 'Rule c: set baru (MAA62043+MAA61041) tidak ada nilainya → hanya lama matched → langsung none sebelum evaluasi weighted avg',
    ],
    [
        'no'            => 4,
        'method'        => 'test_lama_when_weighted_avg_passes_with_all_new_curriculum_codes',
        'skenario'      => 'Adendum PKS Lama — kode BARU, nilai baru (rule b)',
        'prodi'         => 'S1 Ilmu Aktuaria',
        'modul'         => 'A10',
        'k1_code'       => 'MAA62043',
        'k1_nama'       => 'Matematika Finansial I',
        'k1_sks'        => 3,
        'k1_kur'        => 'Baru',
        'k2_code'       => 'MAA61041',
        'k2_nama'       => 'Matematika Finansial II',
        'k2_sks'        => 3,
        'k2_kur'        => 'Baru',
        'ta'            => 'Genap 2425 / Ganjil 2425',
        'nh1'           => 'A',
        'gp1'           => 4.0,
        'nh2'           => 'A',
        'gp2'           => 4.0,
        'wavg'          => 4.0,
        'avg_gt35'      => 'Ya',
        'na1'           => 70.0,
        'thr1'          => '—',
        'na1_ok'        => '— (no threshold)',
        'na2'           => 70.0,
        'thr2'          => '—',
        'na2_ok'        => '— (no threshold)',
        'na_all_ok'     => 'Tidak',
        'force_old'     => 'Tidak',
        'eli_lama'      => 'Ya',
        'eli_baru'      => 'Tidak (no threshold)',
        'decision'      => 'lama',
        'biaya'         => 500000,
        'catatan'       => 'Rule b: kode kurikulum BARU tidak memblokir Adendum PKS Lama — cukup weighted avg > 3.5',
    ],
    [
        'no'            => 5,
        'method'        => 'test_none_when_components_are_incomplete',
        'skenario'      => 'Belum eligible — komponen matkul tidak lengkap',
        'prodi'         => 'S1 Ilmu Aktuaria',
        'modul'         => 'A10',
        'k1_code'       => 'MAA62043',
        'k1_nama'       => 'Matematika Finansial I',
        'k1_sks'        => 3,
        'k1_kur'        => 'Baru',
        'k2_code'       => '(tidak ada nilai)',
        'k2_nama'       => 'MAA61041 — tidak diambil',
        'k2_sks'        => 3,
        'k2_kur'        => 'Baru',
        'ta'            => 'Genap 2223',
        'nh1'           => 'A',
        'gp1'           => 4.0,
        'nh2'           => '—',
        'gp2'           => '—',
        'wavg'          => '—',
        'avg_gt35'      => 'Tidak',
        'na1'           => 90.0,
        'thr1'          => '—',
        'na1_ok'        => '—',
        'na2'           => '—',
        'thr2'          => '—',
        'na2_ok'        => '—',
        'na_all_ok'     => 'Tidak',
        'force_old'     => 'N/A',
        'eli_lama'      => 'Tidak',
        'eli_baru'      => 'Tidak',
        'decision'      => 'none',
        'biaya'         => null,
        'catatan'       => 'matchedSets kosong karena MAA61041 tidak punya nilai → langsung none',
    ],
    [
        'no'            => 6,
        'method'        => 'test_none_when_one_component_has_failing_grade_e',
        'skenario'      => 'Belum eligible — nilai E pada satu komponen',
        'prodi'         => 'S1 Ilmu Aktuaria',
        'modul'         => 'A10',
        'k1_code'       => 'MAA62043',
        'k1_nama'       => 'Matematika Finansial I',
        'k1_sks'        => 3,
        'k1_kur'        => 'Baru',
        'k2_code'       => 'MAA61041',
        'k2_nama'       => 'Matematika Finansial II',
        'k2_sks'        => 3,
        'k2_kur'        => 'Baru',
        'ta'            => 'Genap 2223',
        'nh1'           => 'A',
        'gp1'           => 4.0,
        'nh2'           => 'E',
        'gp2'           => 0.0,
        'wavg'          => '—',
        'avg_gt35'      => 'Tidak',
        'na1'           => 90.0,
        'thr1'          => '—',
        'na1_ok'        => '—',
        'na2'           => 40.0,
        'thr2'          => '—',
        'na2_ok'        => '—',
        'na_all_ok'     => 'Tidak',
        'force_old'     => 'N/A',
        'eli_lama'      => 'Tidak',
        'eli_baru'      => 'Tidak',
        'decision'      => 'none',
        'biaya'         => null,
        'catatan'       => 'NH=E langsung didiskualifikasi di matchComponents() → matched=null',
    ],
    [
        'no'            => 7,
        'method'        => 'test_lama_fails_when_weighted_average_is_exactly_3_5',
        'skenario'      => 'Belum eligible — weighted avg TEPAT 3.5 (batas strictly > 3.5)',
        'prodi'         => 'S1 Ilmu Aktuaria',
        'modul'         => 'A10',
        'k1_code'       => 'MAA62043',
        'k1_nama'       => 'Matematika Finansial I',
        'k1_sks'        => 3,
        'k1_kur'        => 'Baru',
        'k2_code'       => 'MAA61041',
        'k2_nama'       => 'Matematika Finansial II',
        'k2_sks'        => 3,
        'k2_kur'        => 'Baru',
        'ta'            => 'Genap 2425 / Ganjil 2425',
        'nh1'           => 'B+',
        'gp1'           => 3.5,
        'nh2'           => 'B+',
        'gp2'           => 3.5,
        'wavg'          => 3.5,
        'avg_gt35'      => 'Tidak (= bukan >)',
        'na1'           => 70.0,
        'thr1'          => '—',
        'na1_ok'        => '— (no threshold)',
        'na2'           => 70.0,
        'thr2'          => '—',
        'na2_ok'        => '— (no threshold)',
        'na_all_ok'     => 'Tidak',
        'force_old'     => 'Tidak',
        'eli_lama'      => 'Tidak',
        'eli_baru'      => 'Tidak (no threshold)',
        'decision'      => 'none',
        'biaya'         => null,
        'catatan'       => 'Kode BARU dipakai agar rule c tidak berlaku. Syarat Adendum PKS Lama: LEBIH DARI 3.5, bukan sama dengan. B++B+ = tepat 3.5 → gagal.',
    ],
    [
        'no'            => 8,
        'method'        => 'test_lama_decision_for_module_with_shared_curriculum_codes',
        'skenario'      => 'Adendum PKS Lama — modul A20, kode shared di dua kurikulum',
        'prodi'         => 'S1 Ilmu Aktuaria',
        'modul'         => 'A20',
        'k1_code'       => 'MAA62003',
        'k1_nama'       => 'Statistika Matematika I',
        'k1_sks'        => 3,
        'k1_kur'        => 'Lama & Baru',
        'k2_code'       => 'MAA61007',
        'k2_nama'       => 'Statistika Matematika II',
        'k2_sks'        => 3,
        'k2_kur'        => 'Lama & Baru',
        'ta'            => 'Genap 2223',
        'nh1'           => 'A',
        'gp1'           => 4.0,
        'nh2'           => 'B+',
        'gp2'           => 3.5,
        'wavg'          => 3.75,
        'avg_gt35'      => 'Ya',
        'na1'           => 70.0,
        'thr1'          => '—',
        'na1_ok'        => '—',
        'na2'           => 70.0,
        'thr2'          => '—',
        'na2_ok'        => '—',
        'na_all_ok'     => 'Tidak',
        'force_old'     => 'Ya',
        'eli_lama'      => 'Ya',
        'eli_baru'      => 'Tidak (diblokir)',
        'decision'      => 'lama',
        'biaya'         => 500000,
        'catatan'       => 'Kode MAA62003/MAA61007 muncul di kurikulum lama DAN baru modul A20 Aktuaria',
    ],
    [
        'no'            => 9,
        'method'        => 'test_uses_highest_na_when_student_has_duplicate_grade_rows',
        'skenario'      => 'Retake — sistem pakai NA tertinggi (bukan NA terlama)',
        'prodi'         => 'S1 Ilmu Aktuaria',
        'modul'         => 'A10',
        'k1_code'       => 'MAA62043 (2 baris)',
        'k1_nama'       => 'Matematika Finansial I — retake',
        'k1_sks'        => 3,
        'k1_kur'        => 'Baru',
        'k2_code'       => 'MAA61041',
        'k2_nama'       => 'Matematika Finansial II',
        'k2_sks'        => 3,
        'k2_kur'        => 'Baru',
        'ta'            => 'MAA62043: Genap 2324 (NA=60) & Genap 2425 (NA=90) → dipakai: 2425',
        'nh1'           => 'A (best)',
        'gp1'           => 4.0,
        'nh2'           => 'A',
        'gp2'           => 4.0,
        'wavg'          => 4.0,
        'avg_gt35'      => 'Ya',
        'na1'           => '90 (best; percobaan lama=60)',
        'thr1'          => 80.0,
        'na1_ok'        => 'Ya',
        'na2'           => 85.0,
        'thr2'          => 80.0,
        'na2_ok'        => 'Ya',
        'na_all_ok'     => 'Ya',
        'force_old'     => 'Tidak (best grade dari 2425)',
        'eli_lama'      => 'Ya',
        'eli_baru'      => 'Ya (bukti NA=90 dipakai)',
        'decision'      => 'lama',
        'biaya'         => 500000,
        'catatan'       => 'eligibleBaru=true membuktikan sistem pakai NA=90 (bukan NA=60). Lama tetap menang karena weighted avg=4.0',
    ],
    [
        'no'            => 10,
        'method'        => 'test_old_year_grade_forces_pks_lama_even_when_na_meets_threshold',
        'skenario'      => 'forceOldScheme — TA 23/24 blokir PKS Baru meski NA tinggi',
        'prodi'         => 'S1 Ilmu Aktuaria',
        'modul'         => 'A10',
        'k1_code'       => 'MAA62043',
        'k1_nama'       => 'Matematika Finansial I',
        'k1_sks'        => 3,
        'k1_kur'        => 'Baru',
        'k2_code'       => 'MAA61041',
        'k2_nama'       => 'Matematika Finansial II',
        'k2_sks'        => 3,
        'k2_kur'        => 'Baru',
        'ta'            => 'Genap 2324 / Ganjil 2324',
        'nh1'           => 'A',
        'gp1'           => 4.0,
        'nh2'           => 'A',
        'gp2'           => 4.0,
        'wavg'          => 4.0,
        'avg_gt35'      => 'Ya',
        'na1'           => 95.0,
        'thr1'          => 80.0,
        'na1_ok'        => 'Ya (tapi diblokir)',
        'na2'           => 92.0,
        'thr2'          => 80.0,
        'na2_ok'        => 'Ya (tapi diblokir)',
        'na_all_ok'     => 'Ya (tapi PKS Baru diblokir)',
        'force_old'     => 'Ya',
        'eli_lama'      => 'Ya',
        'eli_baru'      => 'Tidak (diblokir TA 23/24)',
        'decision'      => 'lama',
        'biaya'         => 500000,
        'catatan'       => 'Meski NA=95/92 jauh di atas threshold=80, PKS Baru tidak berlaku karena TA 23/24',
    ],
    [
        'no'            => 11,
        'method'        => 'test_mixed_years_any_old_grade_forces_pks_lama',
        'skenario'      => 'forceOldScheme — campuran TA (satu lama cukup memblokir PKS Baru)',
        'prodi'         => 'S1 Ilmu Aktuaria',
        'modul'         => 'A10',
        'k1_code'       => 'MAA62043',
        'k1_nama'       => 'Matematika Finansial I',
        'k1_sks'        => 3,
        'k1_kur'        => 'Baru',
        'k2_code'       => 'MAA61041',
        'k2_nama'       => 'Matematika Finansial II',
        'k2_sks'        => 3,
        'k2_kur'        => 'Baru',
        'ta'            => 'MAA62043: Genap 2324 | MAA61041: Ganjil 2425',
        'nh1'           => 'A',
        'gp1'           => 4.0,
        'nh2'           => 'A',
        'gp2'           => 4.0,
        'wavg'          => 4.0,
        'avg_gt35'      => 'Ya',
        'na1'           => 90.0,
        'thr1'          => 80.0,
        'na1_ok'        => 'Ya (tapi diblokir)',
        'na2'           => 88.0,
        'thr2'          => 80.0,
        'na2_ok'        => 'Ya (tapi diblokir)',
        'na_all_ok'     => 'Ya (tapi PKS Baru diblokir)',
        'force_old'     => 'Ya (cukup satu nilai lama)',
        'eli_lama'      => 'Ya',
        'eli_baru'      => 'Tidak (diblokir)',
        'decision'      => 'lama',
        'biaya'         => 500000,
        'catatan'       => 'Rule "any": cukup satu nilai dari TA ≤ 23/24 → forceOldScheme aktif untuk semua komponen',
    ],
    [
        'no'            => 12,
        'method'        => 'test_old_year_forces_lama_even_for_new_curriculum_codes',
        'skenario'      => 'forceOldScheme — TA 22/23 + kode BARU → tetap Adendum PKS Lama',
        'prodi'         => 'S1 Ilmu Aktuaria',
        'modul'         => 'A10',
        'k1_code'       => 'MAA62043',
        'k1_nama'       => 'Matematika Finansial I',
        'k1_sks'        => 3,
        'k1_kur'        => 'Baru',
        'k2_code'       => 'MAA61041',
        'k2_nama'       => 'Matematika Finansial II',
        'k2_sks'        => 3,
        'k2_kur'        => 'Baru',
        'ta'            => 'Genap 2223 / Ganjil 2223',
        'nh1'           => 'A',
        'gp1'           => 4.0,
        'nh2'           => 'A',
        'gp2'           => 4.0,
        'wavg'          => 4.0,
        'avg_gt35'      => 'Ya',
        'na1'           => 70.0,
        'thr1'          => '—',
        'na1_ok'        => '—',
        'na2'           => 70.0,
        'thr2'          => '—',
        'na2_ok'        => '—',
        'na_all_ok'     => 'Tidak',
        'force_old'     => 'Ya',
        'eli_lama'      => 'Ya',
        'eli_baru'      => 'Tidak (diblokir)',
        'decision'      => 'lama',
        'biaya'         => 500000,
        'catatan'       => 'Kombinasi forceOldScheme+rule b: tahun lama + kode baru → tetap Adendum PKS Lama',
    ],
    [
        'no'            => 13,
        'method'        => 'test_matematika_student_evaluates_mam_courses_for_a20',
        'skenario'      => 'S1 Matematika — matkul MAM dievaluasi (bukan MAA)',
        'prodi'         => 'S1 Matematika',
        'modul'         => 'A20',
        'k1_code'       => 'MAM60601',
        'k1_nama'       => 'Pengantar Peluang+',
        'k1_sks'        => 3,
        'k1_kur'        => 'Baru (Matematika)',
        'k2_code'       => 'MAM60602',
        'k2_nama'       => 'Pengantar Statistika Matematika+',
        'k2_sks'        => 4,
        'k2_kur'        => 'Baru (Matematika)',
        'ta'            => 'Genap 2223',
        'nh1'           => 'A',
        'gp1'           => 4.0,
        'nh2'           => 'A',
        'gp2'           => 4.0,
        'wavg'          => 4.0,
        'avg_gt35'      => 'Ya',
        'na1'           => 85.0,
        'thr1'          => '—',
        'na1_ok'        => '—',
        'na2'           => 80.0,
        'thr2'          => '—',
        'na2_ok'        => '—',
        'na_all_ok'     => 'Tidak',
        'force_old'     => 'Ya',
        'eli_lama'      => 'Ya',
        'eli_baru'      => 'Tidak (diblokir)',
        'decision'      => 'lama',
        'biaya'         => 500000,
        'catatan'       => 'Matematika hanya match kursus MAM. SKS berbeda: MAM60601=3, MAM60602=4 → avg=(4×3+4×4)/7=4.0',
    ],
    [
        'no'            => 14,
        'method'        => 'test_matematika_student_is_not_matched_by_aktuaria_courses',
        'skenario'      => 'S1 Matematika — kursus MAA (Aktuaria) tidak cocok',
        'prodi'         => 'S1 Matematika',
        'modul'         => 'A20',
        'k1_code'       => 'MAA62003',
        'k1_nama'       => 'Statistika Matematika I (Aktuaria)',
        'k1_sks'        => 3,
        'k1_kur'        => 'Aktuaria',
        'k2_code'       => 'MAA61007',
        'k2_nama'       => 'Statistika Matematika II (Aktuaria)',
        'k2_sks'        => 3,
        'k2_kur'        => 'Aktuaria',
        'ta'            => 'Genap 2425',
        'nh1'           => 'A',
        'gp1'           => 4.0,
        'nh2'           => 'A',
        'gp2'           => 4.0,
        'wavg'          => '— (tidak matched)',
        'avg_gt35'      => 'N/A',
        'na1'           => 90.0,
        'thr1'          => '—',
        'na1_ok'        => '—',
        'na2'           => 90.0,
        'thr2'          => '—',
        'na2_ok'        => '—',
        'na_all_ok'     => 'N/A',
        'force_old'     => 'N/A',
        'eli_lama'      => 'Tidak',
        'eli_baru'      => 'Tidak',
        'decision'      => 'none',
        'biaya'         => null,
        'catatan'       => 'resolveMatchedSets filter by prodi → tidak ada matched set untuk S1 Matematika dengan kursus MAA',
    ],
    [
        'no'            => 15,
        'method'        => 'test_component_grades_are_included_for_admin_display',
        'skenario'      => 'Struktur output componentGrades tersedia untuk tampilan admin',
        'prodi'         => 'S1 Ilmu Aktuaria',
        'modul'         => 'A10',
        'k1_code'       => 'MAA62043',
        'k1_nama'       => 'Matematika Finansial I',
        'k1_sks'        => 3,
        'k1_kur'        => 'Baru',
        'k2_code'       => 'MAA61041',
        'k2_nama'       => 'Matematika Finansial II',
        'k2_sks'        => 3,
        'k2_kur'        => 'Baru',
        'ta'            => 'Genap 2425 / Ganjil 2425',
        'nh1'           => 'A',
        'gp1'           => 4.0,
        'nh2'           => 'A',
        'gp2'           => 4.0,
        'wavg'          => 4.0,
        'avg_gt35'      => 'Ya',
        'na1'           => 85.0,
        'thr1'          => 80.0,
        'na1_ok'        => 'Ya',
        'na2'           => 90.0,
        'thr2'          => 80.0,
        'na2_ok'        => 'Ya',
        'na_all_ok'     => 'Ya',
        'force_old'     => 'Tidak',
        'eli_lama'      => 'Ya',
        'eli_baru'      => 'Ya',
        'decision'      => 'lama',
        'biaya'         => 500000,
        'catatan'       => 'Test ini hanya cek structure componentGrades[\'baru\'] punya 2 courses dan course[0]=MAA62043',
    ],
];

// ─── Build Spreadsheet ────────────────────────────────────────────────────────
$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()
    ->setTitle('Test Cases EligibilityService')
    ->setDescription('Rekap test case eligibility penyetaraan modul PAI');

// ════════════════════════════════════════════════════════════════════════════
// SHEET 1 — TEST CASES
// ════════════════════════════════════════════════════════════════════════════
$ws = $spreadsheet->getActiveSheet();
$ws->setTitle('Test Cases');

// ─── Header row ─────────────────────────────────────────────────────────────
$headers = [
    'A' => 'No',
    'B' => 'Method Name',
    'C' => 'Skenario',
    'D' => 'Prodi',
    'E' => 'Modul',
    'F' => 'Kursus 1\n(Kode)',
    'G' => 'Kursus 1\n(Nama)',
    'H' => 'SKS 1',
    'I' => 'Kurikulum 1',
    'J' => 'Kursus 2\n(Kode)',
    'K' => 'Kursus 2\n(Nama)',
    'L' => 'SKS 2',
    'M' => 'Kurikulum 2',
    'N' => 'TA Nilai',
    'O' => 'NH 1',
    'P' => 'Grade\nPoint 1',
    'Q' => 'NH 2',
    'R' => 'Grade\nPoint 2',
    'S' => 'Weighted\nAvg',
    'T' => 'Avg > 3.5?\n(Adendum PKS Lama)',
    'U' => 'NA 1',
    'V' => 'Threshold\nNA 1',
    'W' => 'NA 1 ≥\nThreshold?',
    'X' => 'NA 2',
    'Y' => 'Threshold\nNA 2',
    'Z' => 'NA 2 ≥\nThreshold?',
    'AA' => 'Semua NA\n≥ Threshold?',
    'AB' => 'forceOld\nScheme?',
    'AC' => 'eligible\nLama?',
    'AD' => 'eligible\nBaru?',
    'AE' => 'Decision',
    'AF' => 'Biaya (Rp)',
    'AG' => 'Catatan',
];

$row = 1;
foreach ($headers as $col => $label) {
    $ws->setCellValue($col . $row, str_replace('\n', "\n", $label));
    $ws->getStyle($col . $row)->applyFromArray([
        'font'      => ['bold' => true, 'color' => ['argb' => C_HEADER_FONT], 'size' => 10],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => C_HEADER_BG]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]],
    ]);
}
$ws->getRowDimension(1)->setRowHeight(36);

// ─── Data rows ───────────────────────────────────────────────────────────────
foreach ($testCases as $tc) {
    $row++;

    // Decision color
    $decBg   = $tc['decision'] === 'lama' ? C_LAMA_BG   : ($tc['decision'] === 'baru' ? C_BARU_BG   : C_NONE_BG);
    $decFont = $tc['decision'] === 'lama' ? C_LAMA_FONT : ($tc['decision'] === 'baru' ? C_BARU_FONT : C_NONE_FONT);
    $rowBg   = ($row % 2 === 0) ? 'FFFFFFFF' : 'FFF8FAFC';

    $cells = [
        'A' => $tc['no'],
        'B' => $tc['method'],
        'C' => $tc['skenario'],
        'D' => $tc['prodi'],
        'E' => $tc['modul'],
        'F' => $tc['k1_code'],
        'G' => $tc['k1_nama'],
        'H' => $tc['k1_sks'],
        'I' => $tc['k1_kur'],
        'J' => $tc['k2_code'],
        'K' => $tc['k2_nama'],
        'L' => $tc['k2_sks'],
        'M' => $tc['k2_kur'],
        'N' => $tc['ta'],
        'O' => $tc['nh1'],
        'P' => $tc['gp1'],
        'Q' => $tc['nh2'],
        'R' => $tc['gp2'],
        'S' => $tc['wavg'],
        'T' => $tc['avg_gt35'],
        'U' => $tc['na1'],
        'V' => $tc['thr1'],
        'W' => $tc['na1_ok'],
        'X' => $tc['na2'],
        'Y' => $tc['thr2'],
        'Z' => $tc['na2_ok'],
        'AA' => $tc['na_all_ok'],
        'AB' => $tc['force_old'],
        'AC' => $tc['eli_lama'],
        'AD' => $tc['eli_baru'],
        'AE' => strtoupper($tc['decision']),
        'AF' => $tc['biaya'],
        'AG' => $tc['catatan'],
    ];

    foreach ($cells as $col => $val) {
        if ($col === 'AF' && $val !== null) {
            $ws->setCellValueExplicit($col . $row, $val, DataType::TYPE_NUMERIC);
            $ws->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0');
        } else {
            $ws->setCellValue($col . $row, $val ?? '—');
        }

        $style = [
            'font'      => ['size' => 10],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $rowBg]],
            'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]],
        ];

        // Center-align certain columns
        if (in_array($col, ['A','E','H','L','O','P','Q','R','S','T','W','Z','AA','AB','AC','AD','AE','AF'])) {
            $style['alignment']['horizontal'] = Alignment::HORIZONTAL_CENTER;
        }

        // Decision column coloring
        if ($col === 'AE') {
            $style['font']['bold']  = true;
            $style['font']['color'] = ['argb' => $decFont];
            $style['fill']          = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $decBg]];
        }

        // Ya/Tidak coloring
        if (in_array($col, ['T','W','Z','AA','AB','AC','AD'])) {
            if (str_starts_with((string)$val, 'Ya')) {
                $style['font']['color'] = ['argb' => C_YES];
                $style['font']['bold']  = true;
            } elseif (str_starts_with((string)$val, 'Tidak') || $val === '—') {
                $style['font']['color'] = ['argb' => C_NO];
            }
        }

        // Notes column background
        if ($col === 'AG') {
            $style['fill'] = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => C_NOTE_BG]];
        }

        $ws->getStyle($col . $row)->applyFromArray($style);
    }

    $ws->getRowDimension($row)->setRowHeight(-1); // auto
}

// ─── Column widths ───────────────────────────────────────────────────────────
$colWidths = [
    'A'=>5,'B'=>32,'C'=>38,'D'=>18,'E'=>7,
    'F'=>13,'G'=>25,'H'=>6,'I'=>14,
    'J'=>13,'K'=>28,'L'=>6,'M'=>14,
    'N'=>30,'O'=>6,'P'=>8,'Q'=>6,'R'=>8,
    'S'=>10,'T'=>14,'U'=>7,'V'=>10,'W'=>12,
    'X'=>7,'Y'=>10,'Z'=>12,'AA'=>12,
    'AB'=>10,'AC'=>10,'AD'=>10,'AE'=>10,'AF'=>12,'AG'=>50,
];
foreach ($colWidths as $col => $w) {
    $ws->getColumnDimension($col)->setWidth($w);
}

// Freeze header row
$ws->freezePane('A2');

// Auto filter
$lastCol = 'AG';
$lastRow = count($testCases) + 1;
$ws->setAutoFilter("A1:{$lastCol}{$lastRow}");


// ════════════════════════════════════════════════════════════════════════════
// SHEET 2 — DECISION TREE RULES
// ════════════════════════════════════════════════════════════════════════════
$spreadsheet->createSheet();
$ws2 = $spreadsheet->getSheet(1);
$ws2->setTitle('Decision Tree');

$rules = [
    ['ATURAN ELIGIBILITY — EligibilityService', '', ''],
    ['', '', ''],
    ['LANGKAH', 'KONDISI', 'HASIL'],
    ['Pre-check Kurikulum (Rule c)', 'Apakah ada set matkul kurikulum BARU yang lengkap diambil mahasiswa?', 'TIDAK ada set baru → NONE langsung (tidak bisa disetarakan)'],
    ['Pre-check (Step 0)', 'Ada nilai dari TA 23/24 atau lebih lama? (yearCode ≤ 2324)', 'forceOldScheme = true → PKS Baru DIBLOKIR'],
    ['Langkah 1', 'Weighted Avg Tertimbang SKS > 3.5 (dari set mana pun — lama ATAU baru)', 'decision = LAMA | Biaya Rp500.000'],
    ['Langkah 2', 'NA ≥ threshold persentil di SEMUA komponen (hanya jika forceOldScheme=false)', 'decision = BARU | Biaya Rp550.000'],
    ['Fallback', 'Tidak lolos keduanya', 'decision = NONE | Belum eligible'],
    ['', '', ''],
    ['CATATAN PENTING', '', ''],
    ['Rule c (pre-check)', 'Jika HANYA set kurikulum lama yang matched → NONE. Diperlukan nilai dari matkul kurikulum terbaru.', ''],
    ['3 Skenario Matching', '(a) Baru+Lama matched → rule a, lama diutamakan | (b) Baru saja → rule b, lama/baru | (c) Lama saja → NONE', ''],
    ['Adendum PKS Lama', 'Berlaku untuk kode kurikulum LAMA maupun BARU (tidak ada pembatasan kode)', ''],
    ['Adendum PKS Lama', 'Syarat: weighted avg LEBIH DARI 3.5 (strictly greater — tepat 3.5 = GAGAL)', ''],
    ['PKS Baru', 'Hanya berlaku jika SEMUA nilai dari TA 24/25 ke atas', ''],
    ['PKS Baru', 'Hanya dipilih jika TIDAK lolos Adendum PKS Lama', ''],
    ['Prioritas', 'Adendum PKS Lama SELALU diutamakan jika lolos (Rp500.000 < Rp550.000)', ''],
    ['Retake', 'Jika ada >1 baris nilai untuk matkul yang sama → pakai NA TERTINGGI', ''],
    ['Prodi', 'Matching matkul komponen berdasarkan prodi: S1 Aktuaria→MAA, S1 Matematika→MAM', ''],
    ['', '', ''],
    ['KONVERSI NH → GRADE POINT', '', ''],
    ['NH', 'Grade Point', ''],
    ['A', '4.0', ''],
    ['B+', '3.5', ''],
    ['B', '3.0', ''],
    ['C+', '2.5', ''],
    ['C', '2.0', ''],
    ['D', '1.0', ''],
    ['E', '0.0 (DIDISKUALIFIKASI)', ''],
];

foreach ($rules as $i => $ruleRow) {
    $rowNum = $i + 1;
    [$col1, $col2, $col3] = $ruleRow;

    $ws2->setCellValue('A' . $rowNum, $col1);
    $ws2->setCellValue('B' . $rowNum, $col2);
    $ws2->setCellValue('C' . $rowNum, $col3);

    // Title row
    if ($rowNum === 1) {
        $ws2->getStyle("A{$rowNum}:C{$rowNum}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => C_HEADER_FONT]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => C_HEADER_BG]],
        ]);
        $ws2->mergeCells("A{$rowNum}:C{$rowNum}");
    }
    // Section headers
    if (in_array($col1, ['LANGKAH','CATATAN PENTING','KONVERSI NH → GRADE POINT'])) {
        $ws2->getStyle("A{$rowNum}:C{$rowNum}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => C_HEADER_FONT]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => C_HEADER_BG]],
        ]);
    }
    // Decision rows
    if ($col1 === 'Langkah 1') {
        $ws2->getStyle("C{$rowNum}")->getFont()->getColor()->setARGB(C_LAMA_FONT);
        $ws2->getStyle("C{$rowNum}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(C_LAMA_BG);
    }
    if ($col1 === 'Langkah 2') {
        $ws2->getStyle("C{$rowNum}")->getFont()->getColor()->setARGB(C_BARU_FONT);
        $ws2->getStyle("C{$rowNum}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(C_BARU_BG);
    }
    if ($col1 === 'Fallback') {
        $ws2->getStyle("C{$rowNum}")->getFont()->getColor()->setARGB(C_NONE_FONT);
        $ws2->getStyle("C{$rowNum}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(C_NONE_BG);
    }

    $ws2->getStyle("A{$rowNum}:C{$rowNum}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
    if (!in_array($rowNum, [1])) {
        $ws2->getStyle("A{$rowNum}:C{$rowNum}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFCBD5E1');
    }
}

$ws2->getColumnDimension('A')->setWidth(25);
$ws2->getColumnDimension('B')->setWidth(65);
$ws2->getColumnDimension('C')->setWidth(40);


// ════════════════════════════════════════════════════════════════════════════
// SHEET 3 — COVERAGE & SKENARIO BELUM DITEST
// ════════════════════════════════════════════════════════════════════════════
$spreadsheet->createSheet();
$ws3 = $spreadsheet->getSheet(2);
$ws3->setTitle('Coverage & Missing');

$coverage = [
    ['COVERAGE DECISION TREE', '', ''],
    ['', '', ''],
    ['Cabang', 'Ter-cover oleh Test No.', 'Status'],
    ['PKS Baru (weighted avg gagal, NA lolos persentil)', '1', '✅ Ada'],
    ['Rule a: set baru+lama matched → lama diutamakan (lolos keduanya)', '2', '✅ Ada'],
    ['Rule c: HANYA lama matched → NONE (meski avg tinggi)', '3', '✅ Ada'],
    ['Adendum PKS Lama (kode BARU, nilai baru — rule b)', '4', '✅ Ada'],
    ['None — komponen tidak lengkap', '5', '✅ Ada'],
    ['None — nilai E pada satu komponen', '6', '✅ Ada'],
    ['None — weighted avg tepat 3.5 (tidak lolos)', '7', '✅ Ada'],
    ['Adendum PKS Lama — modul A20, kode shared', '8', '✅ Ada'],
    ['Retake — sistem pakai NA tertinggi', '9', '✅ Ada'],
    ['forceOldScheme — TA 23/24 blokir PKS Baru', '10', '✅ Ada'],
    ['forceOldScheme — campuran TA (satu lama cukup)', '11', '✅ Ada'],
    ['forceOldScheme — TA 22/23 + kode baru → lama', '12', '✅ Ada'],
    ['Prodi Matematika — evaluasi kursus MAM', '13', '✅ Ada'],
    ['Prodi Matematika — kursus MAA tidak cocok', '14', '✅ Ada'],
    ['Output componentGrades untuk admin', '15', '✅ Ada'],
    ['', '', ''],
    ['SKENARIO YANG BELUM ADA TEST', '', ''],
    ['', '', ''],
    ['Skenario Potensial', 'Deskripsi', 'Prioritas'],
    [
        'A. Retake dengan NA rendah → tetap PKS Baru',
        'Retake: percobaan terbaik punya NH rendah (C) sehingga weighted avg ≤ 3.5 → PKS Baru. Memastikan retake logic benar di jalur PKS Baru.',
        'Medium',
    ],
    [
        'B. S1 Matematika + nilai TA 24/25 + weighted avg lolos (rule b)',
        'Mahasiswa Matematika dengan kursus MAM, nilai TA 24/25, NH tinggi → weighted avg > 3.5 → Adendum PKS Lama (rule b untuk Matematika). Saat ini test 13 pakai TA 22/23.',
        'Medium',
    ],
    [
        'C. NA tepat di batas threshold (NA = threshold_na)',
        'NA = 80.0 dan threshold = 80.0 → syarat NA ≥ threshold → harus LOLOS. Memastikan boundary condition tidak off-by-one.',
        'Tinggi',
    ],
    [
        'D. Semua komponen nilai E',
        'Semua matkul dapat nilai E → matchedSets kosong. Sama dengan kasus 5 tapi edge case lebih ekstrem.',
        'Rendah',
    ],
    [
        'E. Modul A30–A70',
        'Semua test saat ini pakai A10 atau A20. Tidak ada test untuk A30, A40, A50, A60, A70.',
        'Rendah (logic sama)',
    ],
    [
        'F. forceOldScheme aktif TAPI weighted avg gagal',
        'Ada nilai TA 23/24 (PKS Baru diblokir), tapi NH rendah (B+ = tepat 3.5 atau C) → weighted avg ≤ 3.5 → none. Memastikan tidak ada "kebocoran" ke PKS Baru.',
        'Tinggi',
    ],
    [
        'G. Mahasiswa punya nilai untuk DUA kurikulum sekaligus (baru DAN lama)',
        'Student punya nilai MAA62043+MAA61041 (baru) DAN MAA62009+MAA61015 (lama) → dua matched set. Sistem harus evaluasi keduanya dan pilih yang lama karena lebih murah.',
        'Medium',
    ],
    [
        'H. PKS Baru tapi satu matkul NA tepat di bawah threshold',
        'NA matkul 1 = 79.9, threshold = 80.0 → GAGAL. Memastikan satu kegagalan komponen sudah cukup membuat eligibleBaru=false.',
        'Tinggi',
    ],
];

foreach ($coverage as $i => $r) {
    $rowNum = $i + 1;
    $ws3->setCellValue('A' . $rowNum, $r[0]);
    $ws3->setCellValue('B' . $rowNum, $r[1]);
    $ws3->setCellValue('C' . $rowNum, $r[2]);

    if ($rowNum === 1) {
        $ws3->getStyle("A{$rowNum}:C{$rowNum}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => C_HEADER_FONT]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => C_HEADER_BG]],
        ]);
        $ws3->mergeCells("A{$rowNum}:C{$rowNum}");
    }
    if (in_array($r[0], ['Cabang', 'Skenario Potensial'])) {
        $ws3->getStyle("A{$rowNum}:C{$rowNum}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => C_HEADER_FONT]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => C_HEADER_BG]],
        ]);
    }
    if ($r[0] === 'SKENARIO YANG BELUM ADA TEST') {
        $ws3->getStyle("A{$rowNum}:C{$rowNum}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => C_NONE_FONT]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => C_NONE_BG]],
        ]);
        $ws3->mergeCells("A{$rowNum}:C{$rowNum}");
    }
    // Status color
    if (str_contains((string)$r[2], '✅')) {
        $ws3->getStyle("C{$rowNum}")->getFont()->getColor()->setARGB(C_YES);
        $ws3->getStyle("C{$rowNum}")->getFont()->setBold(true);
    }
    // Priority color
    if ($r[2] === 'Tinggi') {
        $ws3->getStyle("C{$rowNum}")->getFont()->getColor()->setARGB(C_NO);
        $ws3->getStyle("C{$rowNum}")->getFont()->setBold(true);
    } elseif ($r[2] === 'Medium') {
        $ws3->getStyle("C{$rowNum}")->getFont()->getColor()->setARGB('FFd97706');
    }

    $ws3->getStyle("A{$rowNum}:C{$rowNum}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
    if ($rowNum > 1) {
        $ws3->getStyle("A{$rowNum}:C{$rowNum}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFCBD5E1');
    }
}

$ws3->getColumnDimension('A')->setWidth(42);
$ws3->getColumnDimension('B')->setWidth(70);
$ws3->getColumnDimension('C')->setWidth(12);

// ─── Set active sheet ke sheet 1 ─────────────────────────────────────────────
$spreadsheet->setActiveSheetIndex(0);

// ─── Simpan ──────────────────────────────────────────────────────────────────
$outputPath = __DIR__ . '/../docs/test-cases-eligibility.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($outputPath);

echo "✅ File tersimpan: {$outputPath}\n";
