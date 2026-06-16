<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\PaiModule;
use App\Models\Student;
use App\Models\Submission;
use App\Models\SubmissionCourse;
use App\Models\User;
use Database\Seeders\CourseSeeder;
use Database\Seeders\ModuleCourseSeeder;
use Database\Seeders\PaiModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmissionReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PaiModuleSeeder::class, CourseSeeder::class, ModuleCourseSeeder::class]);
    }

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin])->fresh();
    }

    private function makeSubmission(Student $student, string $moduleCode = 'A10', string $status = 'pending'): Submission
    {
        $module = PaiModule::where('code', $moduleCode)->firstOrFail();
        $course = Course::where('code', 'MAA62043')->firstOrFail();

        $submission = Submission::create([
            'student_id' => $student->id,
            'pai_module_id' => $module->id,
            'scheme' => 'baru',
            'price' => 550000,
            'status' => $status,
        ]);

        SubmissionCourse::create([
            'submission_id' => $submission->id,
            'course_id' => $course->id,
            'na' => 90.0,
            'nh' => 'A',
            'grade_point' => 4.0,
        ]);

        return $submission;
    }

    public function test_admin_sees_student_list_with_recap_counts(): void
    {
        $admin = $this->makeAdmin();
        $student = Student::factory()->create(['nama' => 'Budi Santoso']);

        $this->makeSubmission($student, 'A10', 'approved');
        $this->makeSubmission($student, 'A20', 'pending');

        $response = $this->actingAs($admin)->get(route('admin.students.index'));

        $response->assertOk();
        $response->assertSee('Budi Santoso');
    }

    public function test_admin_sees_submission_detail_with_components(): void
    {
        $admin = $this->makeAdmin();
        $student = Student::factory()->create();
        $this->makeSubmission($student);

        $response = $this->actingAs($admin)->get(route('admin.students.show', $student));

        $response->assertOk();
        $response->assertSee('MAA62043');
        $response->assertSee('Pending');
    }

    public function test_admin_can_approve_pending_submission(): void
    {
        $admin = $this->makeAdmin();
        $student = Student::factory()->create();
        $submission = $this->makeSubmission($student);

        $response = $this->actingAs($admin)->post(route('admin.submissions.approve', $submission));

        $response->assertRedirect();
        $submission->refresh();
        $this->assertSame('approved', $submission->status);
        $this->assertSame($admin->id, $submission->reviewed_by);
        $this->assertNotNull($submission->reviewed_at);
    }

    public function test_admin_can_reject_pending_submission_with_reason(): void
    {
        $admin = $this->makeAdmin();
        $student = Student::factory()->create();
        $submission = $this->makeSubmission($student);

        $response = $this->actingAs($admin)->post(route('admin.submissions.reject', $submission), [
            'rejection_reason' => 'Data nilai tidak sesuai.',
        ]);

        $response->assertRedirect();
        $submission->refresh();
        $this->assertSame('rejected', $submission->status);
        $this->assertSame('Data nilai tidak sesuai.', $submission->rejection_reason);
    }

    public function test_reject_requires_reason(): void
    {
        $admin = $this->makeAdmin();
        $student = Student::factory()->create();
        $submission = $this->makeSubmission($student);

        $response = $this->actingAs($admin)->post(route('admin.submissions.reject', $submission), []);

        $response->assertSessionHasErrors('rejection_reason');
        $submission->refresh();
        $this->assertSame('pending', $submission->status);
    }

    public function test_cannot_approve_already_processed_submission(): void
    {
        $admin = $this->makeAdmin();
        $student = Student::factory()->create();
        $submission = $this->makeSubmission($student, 'A10', 'approved');

        $response = $this->actingAs($admin)->post(route('admin.submissions.approve', $submission));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_non_admin_cannot_access_admin_student_list(): void
    {
        $student = Student::factory()->create();

        $this->actingAs($student->user)
            ->get(route('admin.students.index'))
            ->assertForbidden();
    }

    public function test_non_admin_cannot_approve_submission(): void
    {
        $student = Student::factory()->create();
        $submission = $this->makeSubmission($student);

        $this->actingAs($student->user)
            ->post(route('admin.submissions.approve', $submission))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.students.index'))
            ->assertRedirect(route('login'));
    }
}
