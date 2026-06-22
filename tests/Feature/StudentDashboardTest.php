<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseGrade;
use App\Models\CourseThreshold;
use App\Models\PaiModule;
use App\Models\Student;
use App\Models\Submission;
use App\Support\GradeScale;
use Database\Seeders\CourseSeeder;
use Database\Seeders\ModuleCourseSeeder;
use Database\Seeders\PaiModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PaiModuleSeeder::class, CourseSeeder::class, ModuleCourseSeeder::class]);
    }

    private function giveGrade(Student $student, string $courseCode, float $na, string $nh): void
    {
        $course = Course::where('code', $courseCode)->firstOrFail();

        CourseGrade::create([
            'course_id' => $course->id,
            'semester' => 'Genap 2425', // TA 24/25+ agar PKS Baru tidak diblokir aturan tahun lama
            'no_induk' => $student->no_induk,
            'nama' => $student->nama,
            'na' => $na,
            'nh' => $nh,
            'grade_point' => GradeScale::toWeight($nh),
        ]);
    }

    private function setThreshold(string $courseCode, float $thresholdNa): void
    {
        $course = Course::where('code', $courseCode)->firstOrFail();

        CourseThreshold::create([
            'course_id' => $course->id,
            'percentile' => 80,
            'threshold_na' => $thresholdNa,
            'computed_at' => now(),
        ]);
    }

    public function test_dashboard_shows_three_states_correctly(): void
    {
        $student = Student::factory()->create();

        // A10 -> eligible PKS Baru (NH='C' → grade_point=2.0 ≤ 3.5 → eligibleLama=false).
        $this->setThreshold('MAA62043', 80.0);
        $this->setThreshold('MAA61041', 80.0);
        $this->giveGrade($student, 'MAA62043', 85.0, 'C');
        $this->giveGrade($student, 'MAA61041', 90.0, 'C');

        // A30 -> eligible Adendum PKS Lama (lewat kode kurikulum lama MAA61009).
        $this->giveGrade($student, 'MAA62004', 70.0, 'A');
        $this->giveGrade($student, 'MAA61009', 70.0, 'B+');

        // A40 -> dibiarkan tanpa nilai sama sekali -> belum eligible.

        $response = $this->actingAs($student->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Eligible (PKS Baru)');
        $response->assertSee('Eligible (Adendum PKS Lama)');
        $response->assertSee('Belum Eligible');
    }

    public function test_dashboard_shows_pending_status_instead_of_eligible_badge(): void
    {
        $student = Student::factory()->create();
        $module = PaiModule::where('code', 'A10')->firstOrFail();

        $this->setThreshold('MAA62043', 80.0);
        $this->setThreshold('MAA61041', 80.0);
        $this->giveGrade($student, 'MAA62043', 85.0, 'A');
        $this->giveGrade($student, 'MAA61041', 90.0, 'A');

        Submission::create([
            'student_id' => $student->id,
            'pai_module_id' => $module->id,
            'scheme' => 'baru',
            'price' => 550000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($student->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Menunggu review');
        $response->assertDontSee('Eligible (PKS Baru)');
    }

    public function test_dashboard_handles_missing_student_profile_gracefully(): void
    {
        // ->fresh() penting: factory()->create() tidak refetch kolom yang
        // dapat DB default (role), jadi in-memory object-nya null sampai
        // di-refresh dari DB (lihat juga catatan di CLAUDE.md).
        $user = \App\Models\User::factory()->create()->fresh();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('belum lengkap');
    }
}
