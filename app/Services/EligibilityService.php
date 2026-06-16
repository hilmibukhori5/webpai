<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseGrade;
use App\Models\PaiModule;
use App\Models\Student;
use Illuminate\Support\Collection;

/**
 * Mesin eligibility penyetaraan modul PAI. Ikuti PERSIS docs/spec.md bagian 4.
 *
 * Alur evaluate():
 * 1. Untuk tiap curriculum (baru, lama), cek apakah mahasiswa LENGKAP
 *    (punya nilai non-E untuk semua matkul komponen modul itu di curriculum
 *    tersebut). Curriculum yang tidak lengkap dilewati ("matched set").
 * 2. Kalau tidak ada satupun curriculum yang lengkap -> decision=none,
 *    alasan "belum lengkap".
 * 3. eligible_baru = true kalau ADA matched set yang lolos percentile (4a).
 *    eligible_lama = true kalau ADA matched set yang lolos rata-rata bobot
 *    tertimbang SKS > 3.5 (4b).
 * 4. Decision tree (4c): baru diutamakan. Kalau cuma lama yang lolos, cek
 *    apakah matched set yang melolos-kan itu mengandung curriculum "lama"
 *    -> decision=lama. Kalau semua matched set yang lolos cuma curriculum
 *    "baru" -> decision=none (PKS Lama bukan celah buat kurikulum baru).
 */
class EligibilityService
{
    public function evaluate(Student $student, PaiModule $module): EligibilityResult
    {
        $matchedSets = $this->resolveMatchedSets($student, $module);

        if ($matchedSets->isEmpty()) {
            return EligibilityResult::notEligible(
                reason: 'Belum lulus semua matkul komponen modul ini (ada yang belum diambil atau nilai E).',
            );
        }

        $eligibleBaru = false;
        $percentilePassingCurricula = [];
        $passingLamaCurricula = [];

        foreach ($matchedSets as $curriculum => $matched) {
            if ($this->passesPercentile($matched)) {
                $eligibleBaru = true;
                $percentilePassingCurricula[] = $curriculum;
            }

            if ($this->weightedAverage($matched) > 3.5) {
                $passingLamaCurricula[] = $curriculum;
            }
        }

        $eligibleLama = ! empty($passingLamaCurricula);
        $componentGrades = $this->buildComponentGrades($matchedSets);

        if ($eligibleBaru) {
            return new EligibilityResult(
                eligibleBaru: true,
                eligibleLama: $eligibleLama,
                decision: 'baru',
                price: config('grading.prices.baru'),
                componentGrades: $componentGrades,
                reason: 'Lolos PKS Baru: NA mahasiswa ≥ batas bawah percentile di semua matkul komponen.',
                decidingCurriculum: $percentilePassingCurricula[0],
            );
        }

        if ($eligibleLama) {
            if (in_array('lama', $passingLamaCurricula, true)) {
                return new EligibilityResult(
                    eligibleBaru: false,
                    eligibleLama: true,
                    decision: 'lama',
                    price: config('grading.prices.lama'),
                    componentGrades: $componentGrades,
                    reason: 'Lolos PKS Lama: rata-rata bobot tertimbang SKS > 3.5, matkul berkode kurikulum lama.',
                    decidingCurriculum: 'lama',
                );
            }

            return new EligibilityResult(
                eligibleBaru: false,
                eligibleLama: true,
                decision: 'none',
                price: null,
                componentGrades: $componentGrades,
                reason: 'Lolos syarat PKS Lama, tapi matkul yang diambil berkode kurikulum baru — wajib lewat PKS Baru (percentile).',
            );
        }

        return new EligibilityResult(
            eligibleBaru: false,
            eligibleLama: false,
            decision: 'none',
            price: null,
            componentGrades: $componentGrades,
            reason: 'Belum eligible PKS Baru maupun PKS Lama.',
        );
    }

    /**
     * Cari curriculum (baru/lama) yang komponennya LENGKAP dipenuhi mahasiswa.
     *
     * @return Collection<string, array<int, array{course: Course, grade: CourseGrade, sks: int}>>
     */
    private function resolveMatchedSets(Student $student, PaiModule $module): Collection
    {
        $sets = collect();

        foreach (['baru', 'lama'] as $curriculum) {
            $moduleCourses = $module->moduleCourses()
                ->where('curriculum', $curriculum)
                ->with(['course.threshold'])
                ->get();

            if ($moduleCourses->isEmpty()) {
                continue;
            }

            $matched = $this->matchComponents($student, $moduleCourses->pluck('course'));

            if ($matched !== null) {
                $sets->put($curriculum, $matched);
            }
        }

        return $sets;
    }

    /**
     * Cocokkan tiap course komponen ke nilai terbaik mahasiswa (NA tertinggi
     * kalau ada >1 baris/retake). Return null kalau ADA satu course yang
     * belum diambil atau nilainya E (modul jadi tidak lengkap).
     *
     * @param  Collection<int, Course>  $courses
     * @return array<int, array{course: Course, grade: CourseGrade, sks: int}>|null
     */
    private function matchComponents(Student $student, Collection $courses): ?array
    {
        $matched = [];

        foreach ($courses as $course) {
            $grade = $this->bestGradeFor($student, $course);

            if (! $grade || strtoupper($grade->nh) === 'E') {
                return null;
            }

            $matched[] = ['course' => $course, 'grade' => $grade, 'sks' => $course->sks];
        }

        return $matched;
    }

    /**
     * Ambil baris course_grades dengan NA tertinggi untuk pasangan
     * no_induk + course (resolusi retake/duplikat, dikonfirmasi 2026-06-16).
     */
    private function bestGradeFor(Student $student, Course $course): ?CourseGrade
    {
        return CourseGrade::where('course_id', $course->id)
            ->where('no_induk', $student->no_induk)
            ->orderByDesc('na')
            ->first();
    }

    /**
     * 4a: NA mahasiswa >= batas_bawah (threshold_na) di SEMUA matkul komponen.
     *
     * @param  array<int, array{course: Course, grade: CourseGrade, sks: int}>  $matched
     */
    private function passesPercentile(array $matched): bool
    {
        foreach ($matched as $item) {
            $threshold = $item['course']->threshold;

            if (! $threshold) {
                return false; // belum ada course_thresholds (belum di-recompute)
            }

            if ((float) $item['grade']->na < (float) $threshold->threshold_na) {
                return false;
            }
        }

        return true;
    }

    /**
     * 4b: rata-rata bobot tertimbang SKS = Sigma(bobot_i * sks_i) / Sigma(sks_i).
     *
     * @param  array<int, array{course: Course, grade: CourseGrade, sks: int}>  $matched
     */
    private function weightedAverage(array $matched): float
    {
        $totalWeighted = 0.0;
        $totalSks = 0;

        foreach ($matched as $item) {
            $totalWeighted += (float) $item['grade']->grade_point * $item['sks'];
            $totalSks += $item['sks'];
        }

        return $totalSks > 0 ? $totalWeighted / $totalSks : 0.0;
    }

    /**
     * Rincian nilai per curriculum yang matched, buat ditampilkan ke admin.
     *
     * @param  Collection<string, array<int, array{course: Course, grade: CourseGrade, sks: int}>>  $matchedSets
     */
    private function buildComponentGrades(Collection $matchedSets): array
    {
        return $matchedSets->map(function (array $matched, string $curriculum) {
            return [
                'curriculum' => $curriculum,
                'weighted_average' => round($this->weightedAverage($matched), 4),
                'passes_percentile' => $this->passesPercentile($matched),
                'courses' => array_map(function (array $item) {
                    $threshold = $item['course']->threshold;

                    return [
                        'course_id' => $item['course']->id,
                        'course_code' => $item['course']->code,
                        'course_name' => $item['course']->name,
                        'sks' => $item['sks'],
                        'na' => (float) $item['grade']->na,
                        'nh' => $item['grade']->nh,
                        'grade_point' => (float) $item['grade']->grade_point,
                        'threshold_na' => $threshold ? (float) $threshold->threshold_na : null,
                    ];
                }, $matched),
            ];
        })->all();
    }
}
