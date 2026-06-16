<?php

namespace App\Imports;

use App\Models\CourseGrade;
use App\Support\GradeScale;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

/**
 * Import nilai per matkul per semester (docs/spec.md bagian 8 Fase 2).
 * Kolom file: No Induk, Nama, NA, NH (heading row otomatis di-snake_case-kan
 * oleh WithHeadingRow jadi no_induk, nama, na, nh).
 *
 * Baris yang gagal validasi di-skip (tidak membatalkan seluruh import),
 * lihat failures() dari trait SkipsFailures untuk dilaporkan ke admin.
 */
class CourseGradesImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use Importable;
    use SkipsFailures;

    protected int $importedCount = 0;

    public function __construct(
        protected int $courseId,
        protected string $semester,
    ) {}

    public function model(array $row): CourseGrade
    {
        $nh = strtoupper(trim((string) $row['nh']));

        $this->importedCount++;

        return new CourseGrade([
            'course_id' => $this->courseId,
            'semester' => $this->semester,
            'no_induk' => (string) $row['no_induk'],
            'nama' => $row['nama'],
            'na' => $row['na'],
            'nh' => $nh,
            'grade_point' => GradeScale::toWeight($nh),
        ]);
    }

    public function importedCount(): int
    {
        return $this->importedCount;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'no_induk' => ['required'],
            'nama' => ['required', 'string'],
            'na' => ['required', 'numeric', 'min:0', 'max:100'],
            'nh' => ['required', function ($attribute, $value, $fail) {
                if (! array_key_exists(strtoupper(trim((string) $value)), config('grading.weights'))) {
                    $fail("Nilai huruf \"{$value}\" tidak dikenal.");
                }
            }],
        ];
    }
}
