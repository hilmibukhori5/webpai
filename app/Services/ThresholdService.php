<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseThreshold;
use App\Support\Percentile;

class ThresholdService
{
    /**
     * Recompute course_thresholds.threshold_na untuk satu course, dengan P
     * (percentile) diambil dari modul tempat course itu jadi komponen
     * (docs/spec.md bagian 2 & 4a). NA di-pool dari SEMUA semester.
     *
     * Return null kalau course belum terhubung ke modul, modul belum punya
     * percentile, atau belum ada nilai (course_grades) yang diimport.
     */
    public function recomputeForCourse(Course $course): ?CourseThreshold
    {
        $module = $course->paiModule();

        if (! $module || $module->percentile === null) {
            return null;
        }

        $naValues = $course->grades()
            ->pluck('na')
            ->map(fn ($na) => (float) $na)
            ->all();

        if (empty($naValues)) {
            return null;
        }

        $thresholdNa = Percentile::inc($naValues, (float) $module->percentile);

        return CourseThreshold::updateOrCreate(
            ['course_id' => $course->id],
            [
                'percentile' => $module->percentile,
                'threshold_na' => $thresholdNa,
                'computed_at' => now(),
            ],
        );
    }

    /**
     * Recompute semua course yang punya nilai. Return jumlah course yang
     * berhasil di-recompute (course tanpa nilai/modul dilewati, bukan error).
     */
    public function recomputeAll(): int
    {
        $count = 0;

        Course::all()->each(function (Course $course) use (&$count) {
            if ($this->recomputeForCourse($course) !== null) {
                $count++;
            }
        });

        return $count;
    }
}
