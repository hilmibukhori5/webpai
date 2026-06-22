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

class SubmissionTest extends TestCase
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

    private function makeEligibleBaruStudent(): array
    {
        $student = Student::factory()->create();
        $module = PaiModule::where('code', 'A10')->firstOrFail();

        $this->setThreshold('MAA62043', 80.0);
        $this->setThreshold('MAA61041', 80.0);
        // NH='C' (grade_point=2.0) → weighted avg ≤ 3.5 → eligibleLama=false → decision='baru'
        $this->giveGrade($student, 'MAA62043', 85.0, 'C');
        $this->giveGrade($student, 'MAA61041', 90.0, 'C');

        return [$student, $module];
    }

    public function test_eligible_student_can_submit_and_components_get_snapshotted(): void
    {
        [$student, $module] = $this->makeEligibleBaruStudent();

        $response = $this->actingAs($student->user)->post(route('submissions.store', $module->code), [
            'bersedia_diajukan' => '1',
            'bersedia_bayar' => '1',
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('submissions', [
            'student_id' => $student->id,
            'pai_module_id' => $module->id,
            'scheme' => 'baru',
            'price' => 550000,
            'status' => 'pending',
        ]);

        $submission = Submission::where('student_id', $student->id)->where('pai_module_id', $module->id)->first();
        $this->assertSame(2, $submission->submissionCourses()->count());
    }

    public function test_consent_checkboxes_are_required(): void
    {
        [$student, $module] = $this->makeEligibleBaruStudent();

        $response = $this->actingAs($student->user)->post(route('submissions.store', $module->code), []);

        $response->assertSessionHasErrors(['bersedia_diajukan', 'bersedia_bayar']);
        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_not_eligible_student_is_blocked_from_create_and_store(): void
    {
        $student = Student::factory()->create();
        $module = PaiModule::where('code', 'A10')->firstOrFail(); // tidak ada nilai sama sekali -> none

        $createResponse = $this->actingAs($student->user)->get(route('submissions.create', $module->code));
        $createResponse->assertRedirect(route('dashboard'));
        $createResponse->assertSessionHas('error');

        $storeResponse = $this->actingAs($student->user)->post(route('submissions.store', $module->code), [
            'bersedia_diajukan' => '1',
            'bersedia_bayar' => '1',
        ]);
        $storeResponse->assertRedirect(route('dashboard'));
        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_cannot_submit_again_while_pending(): void
    {
        [$student, $module] = $this->makeEligibleBaruStudent();

        Submission::create([
            'student_id' => $student->id,
            'pai_module_id' => $module->id,
            'scheme' => 'baru',
            'price' => 550000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($student->user)->post(route('submissions.store', $module->code), [
            'bersedia_diajukan' => '1',
            'bersedia_bayar' => '1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseCount('submissions', 1);
    }

    public function test_cannot_submit_again_while_approved(): void
    {
        [$student, $module] = $this->makeEligibleBaruStudent();

        Submission::create([
            'student_id' => $student->id,
            'pai_module_id' => $module->id,
            'scheme' => 'baru',
            'price' => 550000,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($student->user)->post(route('submissions.store', $module->code), [
            'bersedia_diajukan' => '1',
            'bersedia_bayar' => '1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseCount('submissions', 1);
    }

    public function test_can_resubmit_after_rejected_and_snapshot_refreshes(): void
    {
        [$student, $module] = $this->makeEligibleBaruStudent();

        $rejected = Submission::create([
            'student_id' => $student->id,
            'pai_module_id' => $module->id,
            'scheme' => 'baru',
            'price' => 550000,
            'status' => 'rejected',
            'rejection_reason' => 'Data tidak valid.',
        ]);

        $response = $this->actingAs($student->user)->post(route('submissions.store', $module->code), [
            'bersedia_diajukan' => '1',
            'bersedia_bayar' => '1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseCount('submissions', 1); // bukan row baru, row yang sama di-update

        $rejected->refresh();
        $this->assertSame('pending', $rejected->status);
        $this->assertNull($rejected->rejection_reason);
        $this->assertSame(2, $rejected->submissionCourses()->count());
    }
}
