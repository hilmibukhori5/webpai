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
 * 0. Cek tahun akademik dari nilai terbaik tiap matkul yang matched. Jika ADA
 *    satu pun dari tahun ≤ TA 23/24 (kode tahun "2324" atau lebih kecil),
 *    set forceOldScheme=true → PKS Baru dilewati sepenuhnya.
 * 1. Untuk tiap curriculum (baru, lama), cek apakah mahasiswa LENGKAP.
 * 2. Kalau tidak ada satupun curriculum yang lengkap → decision=none.
 * 2b. Rule c: kalau HANYA kurikulum lama yang matched (tidak ada set baru) →
 *     decision=none. Matkul kurikulum lama saja tidak bisa disetarakan.
 * 3. eligible_baru = true kalau ADA matched set yang lolos percentile (4a),
 *    HANYA jika forceOldScheme=false.
 *    eligible_lama = true kalau ADA matched set yang lolos rata-rata bobot
 *    tertimbang SKS > 3.5 (4b) — berlaku untuk kode kurikulum LAMA maupun BARU.
 * 4. Decision tree (4c): Adendum PKS Lama DIUTAMAKAN (lebih murah):
 *    - eligible_lama (kode apapun) → decision=lama
 *    - eligible_baru → decision=baru
 *    - else → decision=none
 */
class EligibilityService
{
    /** Kode tahun akhir yang masih masuk skema lama, inklusif. "2324" = TA 23/24. */
    private const OLD_YEAR_CUTOFF = 2324;

    public function evaluate(Student $student, PaiModule $module): EligibilityResult
    {
        $matchedSets = $this->resolveMatchedSets($student, $module);

        if ($matchedSets->isEmpty()) {
            return EligibilityResult::notEligible(
                reason: 'Belum lulus semua matkul komponen modul ini (ada yang belum diambil atau nilai E).',
            );
        }

        // Rule c: hanya kurikulum lama yang matched (tidak ada set baru maupun campuran) → tidak bisa disetarakan.
        if (! $matchedSets->has('baru') && ! $matchedSets->has('mixed')) {
            return EligibilityResult::notEligible(
                reason: 'Matkul komponen hanya ditemukan di kurikulum lama — penyetaraan tidak dapat dilakukan. Diperlukan nilai matkul dari kurikulum terbaru.',
            );
        }

        // Step 0: cek apakah ada nilai dari TA 23/24 atau sebelumnya.
        $forceOldScheme = $this->matchedSetsHaveOldYearGrade($matchedSets);

        $eligibleBaru = false;
        $percentilePassingCurricula = [];
        $passingLamaCurricula = [];

        foreach ($matchedSets as $curriculum => $matched) {
            // PKS Baru hanya untuk set baru murni. Campuran lama+baru tidak memenuhi syarat PKS Baru.
            if ($curriculum !== 'mixed' && ! $forceOldScheme && $this->passesPercentile($matched)) {
                $eligibleBaru = true;
                $percentilePassingCurricula[] = $curriculum;
            }

            if ($this->weightedAverage($matched) > 3.5) {
                $passingLamaCurricula[] = $curriculum;
            }
        }

        $eligibleLama = ! empty($passingLamaCurricula);
        $componentGrades = $this->buildComponentGrades($matchedSets);

        // Adendum PKS Lama diutamakan — berlaku untuk semua kode kurikulum (lama maupun baru).
        if ($eligibleLama) {
            $decidingCurriculum = in_array('lama', $passingLamaCurricula, true)
                ? 'lama'
                : $passingLamaCurricula[0];

            return new EligibilityResult(
                eligibleBaru: $eligibleBaru,
                eligibleLama: true,
                decision: 'lama',
                price: config('grading.prices.lama'),
                componentGrades: $componentGrades,
                reason: $forceOldScheme
                    ? 'Lolos Adendum PKS Lama: ada nilai dari TA 23/24 atau sebelumnya, rata-rata bobot tertimbang SKS > 3,5.'
                    : 'Lolos Adendum PKS Lama: rata-rata bobot tertimbang SKS > 3,5.',
                decidingCurriculum: $decidingCurriculum,
            );
        }

        if ($eligibleBaru) {
            return new EligibilityResult(
                eligibleBaru: true,
                eligibleLama: false,
                decision: 'baru',
                price: config('grading.prices.baru'),
                componentGrades: $componentGrades,
                reason: 'Lolos PKS Baru: NA mahasiswa ≥ batas bawah percentile di semua matkul komponen.',
                decidingCurriculum: $percentilePassingCurricula[0],
            );
        }

        return new EligibilityResult(
            eligibleBaru: false,
            eligibleLama: false,
            decision: 'none',
            price: null,
            componentGrades: $componentGrades,
            reason: $forceOldScheme
                ? 'Belum eligible Adendum PKS Lama: nilai dari TA 23/24 atau sebelumnya wajib menggunakan skema Adendum PKS Lama, tetapi rata-rata bobot tertimbang SKS tidak memenuhi syarat (harus > 3,5).'
                : 'Belum eligible PKS Baru maupun Adendum PKS Lama.',
        );
    }

    /**
     * Cek apakah ada nilai (best grade) yang semester-nya dari TA 23/24 atau lebih lama.
     * Format semester: "Genap 2324", "Ganjil 2223", dst. — 4 digit di akhir = kode tahun.
     *
     * @param  Collection<string, array<int, array{course: Course, grade: CourseGrade, sks: int}>>  $matchedSets
     */
    private function matchedSetsHaveOldYearGrade(Collection $matchedSets): bool
    {
        foreach ($matchedSets as $matched) {
            foreach ($matched as $item) {
                if ($this->extractYearCode($item['grade']->semester) <= self::OLD_YEAR_CUTOFF) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Ekstrak kode tahun 4-digit dari string semester, mis. "Genap 2324" → 2324.
     * Return 9999 kalau format tidak dikenal (safe default = dianggap tahun baru).
     */
    private function extractYearCode(string $semester): int
    {
        if (preg_match('/(\d{4})$/', $semester, $matches)) {
            return (int) $matches[1];
        }

        return 9999;
    }

    /**
     * Cari set matkul yang dipenuhi mahasiswa. Urutan prioritas:
     *
     * 1. Mixed slot-match: tiap "slot" (didefinisikan dari set baru) boleh diisi
     *    kode baru ATAU kode lama padanannya (dicocokkan via nama matkul).
     *    Kalau semua slot terisi dan ada campuran baru+lama → return {'mixed'}.
     * 2. Complete-set match: semua kode baru ATAU semua kode lama.
     *
     * @return Collection<string, array<int, array{course: Course, grade: CourseGrade, sks: int}>>
     */
    private function resolveMatchedSets(Student $student, PaiModule $module): Collection
    {
        $sets = collect();

        $mixed = $this->tryMixedSlotMatch($student, $module);
        if ($mixed !== null) {
            return $sets->put('mixed', $mixed);
        }

        foreach (['baru', 'lama'] as $curriculum) {
            $moduleCourses = $module->moduleCourses()
                ->where('curriculum', $curriculum)
                ->where('prodi', $student->prodi)
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
     * Coba isi semua slot modul (diambil dari set baru) dengan kode baru ATAU
     * kode lama padanannya. Padanan dicari via nama matkul yang sama.
     *
     * Return null kalau:
     * - Modul tidak punya kedua kurikulum untuk prodi ini, atau
     * - Ada slot yang tidak terpenuhi, atau
     * - Semua slot terisi satu kurikulum saja (ditangani complete-set matching).
     */
    private function tryMixedSlotMatch(Student $student, PaiModule $module): ?array
    {
        $baruMcs = $module->moduleCourses()
            ->where('curriculum', 'baru')
            ->where('prodi', $student->prodi)
            ->with(['course.threshold'])
            ->get();

        $lamaMcs = $module->moduleCourses()
            ->where('curriculum', 'lama')
            ->where('prodi', $student->prodi)
            ->with(['course.threshold'])
            ->get();

        if ($baruMcs->isEmpty() || $lamaMcs->isEmpty()) {
            return null;
        }

        $matched = [];
        $curricula = [];

        foreach ($baruMcs as $baruMc) {
            $grade = $this->bestGradeFor($student, $baruMc->course);

            if ($grade && strtoupper($grade->nh) !== 'E') {
                $matched[] = ['course' => $baruMc->course, 'grade' => $grade, 'sks' => $baruMc->course->sks];
                $curricula[] = 'baru';
                continue;
            }

            // Cari padanan lama via nama matkul yang sama (atau kode yang sama untuk shared course).
            $lamaMc = $lamaMcs->first(fn ($lmc) =>
                $lmc->course->name === $baruMc->course->name
                || $lmc->course->code === $baruMc->course->code
            );

            if ($lamaMc) {
                $lamaGrade = $this->bestGradeFor($student, $lamaMc->course);
                if ($lamaGrade && strtoupper($lamaGrade->nh) !== 'E') {
                    $matched[] = ['course' => $lamaMc->course, 'grade' => $lamaGrade, 'sks' => $lamaMc->course->sks];
                    $curricula[] = 'lama';
                    continue;
                }
            }

            return null; // Slot tidak terpenuhi
        }

        if (count(array_unique($curricula)) < 2) {
            return null; // Bukan campuran — pure baru/lama ditangani complete-set matching
        }

        return $matched;
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
                return false;
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
