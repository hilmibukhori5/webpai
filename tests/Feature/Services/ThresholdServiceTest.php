<?php

namespace Tests\Feature\Services;

use App\Models\Course;
use App\Models\CourseGrade;
use App\Services\ThresholdService;
use App\Support\Percentile;
use Database\Seeders\CourseSeeder;
use Database\Seeders\ModuleCourseSeeder;
use Database\Seeders\PaiModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThresholdServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PaiModuleSeeder::class, CourseSeeder::class, ModuleCourseSeeder::class]);
    }

    public function test_recompute_uses_percentile_from_courses_module(): void
    {
        // MAA62043 = Matematika Finansial I, modul A10, percentile 80.
        $course = Course::where('code', 'MAA62043')->firstOrFail();

        $naValues = [60, 65, 70, 75, 80, 85, 90, 95, 100, 55];

        foreach ($naValues as $na) {
            CourseGrade::create([
                'course_id' => $course->id,
                'semester' => 'Genap 2223',
                'no_induk' => '195020100'.$na,
                'nama' => 'Mahasiswa '.$na,
                'na' => $na,
                'nh' => 'A',
                'grade_point' => 4.0,
            ]);
        }

        $threshold = app(ThresholdService::class)->recomputeForCourse($course);

        $this->assertNotNull($threshold);
        $this->assertSame(80, (int) $threshold->percentile);
        $this->assertEqualsWithDelta(
            Percentile::inc($naValues, 80),
            (float) $threshold->threshold_na,
            0.01,
        );
    }

    public function test_recompute_returns_null_when_course_has_no_grades_yet(): void
    {
        $course = Course::where('code', 'MAA62043')->firstOrFail();

        $threshold = app(ThresholdService::class)->recomputeForCourse($course);

        $this->assertNull($threshold);
    }

    public function test_recompute_pools_na_from_all_semesters(): void
    {
        $course = Course::where('code', 'MAA62043')->firstOrFail();

        CourseGrade::create([
            'course_id' => $course->id, 'semester' => 'Ganjil 2122',
            'no_induk' => '1', 'nama' => 'A', 'na' => 50, 'nh' => 'C', 'grade_point' => 2.0,
        ]);
        CourseGrade::create([
            'course_id' => $course->id, 'semester' => 'Genap 2223',
            'no_induk' => '2', 'nama' => 'B', 'na' => 90, 'nh' => 'A', 'grade_point' => 4.0,
        ]);

        $threshold = app(ThresholdService::class)->recomputeForCourse($course);

        $this->assertEqualsWithDelta(
            Percentile::inc([50, 90], 80),
            (float) $threshold->threshold_na,
            0.01,
        );
    }
}
