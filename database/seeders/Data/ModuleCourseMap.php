<?php

namespace Database\Seeders\Data;

/**
 * Sumber kebenaran tunggal untuk pemetaan Modul PAI <-> Matkul per kurikulum,
 * PERSIS sesuai docs/spec.md bagian 2. Dipakai oleh PaiModuleSeeder,
 * CourseSeeder, dan ModuleCourseSeeder supaya datanya konsisten satu sama lain.
 *
 * ⚠️ CEK ULANG kode & sks ini dengan sumber resmi sebelum di-seed ke produksi
 * (lihat catatan di docs/spec.md bagian 2).
 */
class ModuleCourseMap
{
    /**
     * @return array<string, array{name: string, official_code: string, percentile: int, baru: array<int, array{code: string, name: string, sks: int}>, lama: array<int, array{code: string, name: string, sks: int}>}>
     */
    public static function all(): array
    {
        return [
            'A10' => [
                'name' => 'Matematika Keuangan',
                'official_code' => 'CF1',
                'percentile' => 80,
                'baru' => [
                    ['code' => 'MAA62043', 'name' => 'Matematika Finansial I', 'sks' => 3],
                    ['code' => 'MAA61041', 'name' => 'Matematika Finansial II', 'sks' => 3],
                ],
                'lama' => [
                    ['code' => 'MAA62009', 'name' => 'Matematika Finansial I', 'sks' => 3],
                    ['code' => 'MAA61015', 'name' => 'Matematika Finansial II', 'sks' => 3],
                ],
            ],
            'A20' => [
                'name' => 'Probabilita & Statistika',
                'official_code' => 'CF2',
                'percentile' => 90,
                'baru' => [
                    ['code' => 'MAA62003', 'name' => 'Statistika Matematika I', 'sks' => 3],
                    ['code' => 'MAA61007', 'name' => 'Statistika Matematika II', 'sks' => 3],
                ],
                'lama' => [
                    ['code' => 'MAA62003', 'name' => 'Statistika Matematika I', 'sks' => 3],
                    ['code' => 'MAA61007', 'name' => 'Statistika Matematika II', 'sks' => 3],
                ],
            ],
            'A30' => [
                'name' => 'Ekonomi',
                'official_code' => 'CF3',
                'percentile' => 80,
                'baru' => [
                    ['code' => 'MAA62004', 'name' => 'Pengantar Ekonomi Mikro', 'sks' => 3],
                    ['code' => 'MAA61052', 'name' => 'Pengantar Ekonomi Makro', 'sks' => 3],
                ],
                'lama' => [
                    ['code' => 'MAA62004', 'name' => 'Pengantar Ekonomi Mikro', 'sks' => 3],
                    ['code' => 'MAA61009', 'name' => 'Pengantar Ekonomi Makro', 'sks' => 3],
                ],
            ],
            'A40' => [
                'name' => 'Akuntansi',
                'official_code' => 'CF4',
                'percentile' => 80,
                'baru' => [
                    ['code' => 'MAA62042', 'name' => 'Akuntansi Aktuaria I', 'sks' => 3],
                    ['code' => 'MAA61044', 'name' => 'Akuntansi Aktuaria II', 'sks' => 3],
                ],
                'lama' => [
                    ['code' => 'MAA62007', 'name' => 'Akuntansi Aktuaria I', 'sks' => 2],
                    ['code' => 'MAA61022', 'name' => 'Akuntansi Aktuaria II', 'sks' => 2],
                ],
            ],
            'A50' => [
                'name' => 'Metoda Statistika',
                'official_code' => 'TA1',
                'percentile' => 80,
                'baru' => [
                    ['code' => 'MAA62045', 'name' => 'Pengantar Runtun Waktu', 'sks' => 3],
                    ['code' => 'MAA61016', 'name' => 'Analisis Data Survival', 'sks' => 3],
                    ['code' => 'MAA62047', 'name' => 'Model Linear', 'sks' => 3],
                ],
                'lama' => [
                    ['code' => 'MAA62011', 'name' => 'Pengantar Runtun Waktu', 'sks' => 3],
                    ['code' => 'MAA61016', 'name' => 'Analisis Data Survival', 'sks' => 3],
                    ['code' => 'MAA62023', 'name' => 'Ekonometrika', 'sks' => 2],
                    ['code' => 'MAA62013', 'name' => 'Model Linear', 'sks' => 3],
                ],
            ],
            'A60' => [
                'name' => 'Matematika Aktuaria',
                'official_code' => 'TA3',
                'percentile' => 80,
                'baru' => [
                    ['code' => 'MAA62048', 'name' => 'Matematika Aktuaria I', 'sks' => 3],
                    ['code' => 'MAA61033', 'name' => 'Matematika Aktuaria II', 'sks' => 3],
                ],
                'lama' => [
                    ['code' => 'MAA62028', 'name' => 'Matematika Aktuaria I', 'sks' => 3],
                    ['code' => 'MAA61033', 'name' => 'Matematika Aktuaria II', 'sks' => 3],
                ],
            ],
            'A70' => [
                'name' => 'Pemodelan & Teori Risiko',
                'official_code' => 'TA2',
                'percentile' => 90,
                'baru' => [
                    ['code' => 'MAA62044', 'name' => 'Pemodelan Risiko Aktuaria', 'sks' => 3],
                    ['code' => 'MAA61051', 'name' => 'Teori Risiko & Kredibilitas Aktuaria', 'sks' => 3],
                ],
                'lama' => [
                    ['code' => 'MAA62008', 'name' => 'Pemodelan Aktuaria', 'sks' => 4],
                    ['code' => 'MAA61035', 'name' => 'Teori Risiko Aktuaria', 'sks' => 2],
                ],
            ],
        ];
    }
}
