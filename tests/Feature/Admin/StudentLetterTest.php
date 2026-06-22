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

class StudentLetterTest extends TestCase
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

    private function approveSubmission(Student $student, string $moduleCode, string $scheme, array $courseCodesWithGrade): Submission
    {
        $module = PaiModule::where('code', $moduleCode)->firstOrFail();

        $submission = Submission::create([
            'student_id' => $student->id,
            'pai_module_id' => $module->id,
            'scheme' => $scheme,
            'price' => $scheme === 'lama' ? 500000 : 550000,
            'status' => 'approved',
        ]);

        foreach ($courseCodesWithGrade as $code => [$na, $nh, $gradePoint]) {
            $course = Course::where('code', $code)->firstOrFail();
            SubmissionCourse::create([
                'submission_id' => $submission->id,
                'course_id' => $course->id,
                'na' => $na,
                'nh' => $nh,
                'grade_point' => $gradePoint,
            ]);
        }

        return $submission;
    }

    private function extractDocumentXml(string $docxPath): string
    {
        $zip = new \ZipArchive;
        $zip->open($docxPath);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        return $xml;
    }

    public function test_admin_can_download_letter_with_baru_scheme_numeric_values(): void
    {
        $admin = $this->makeAdmin();
        $student = Student::factory()->create(['nama' => 'Ahmad Fauzi', 'no_induk' => '195020100001']);

        $this->approveSubmission($student, 'A10', 'baru', [
            'MAA62043' => [95.0, 'A', 4.0],
            'MAA61041' => [98.0, 'A', 4.0],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.students.letter', $student));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $docxPath = $response->baseResponse->getFile()->getPathname();
        $xml = $this->extractDocumentXml($docxPath);

        $this->assertStringContainsString('Ahmad Fauzi', $xml);
        $this->assertStringContainsString('195020100001', $xml);
        $this->assertStringContainsString('A10', $xml);
        $this->assertStringContainsString('Matematika Finansial I', $xml);
        $this->assertStringContainsString('95', $xml);
        $this->assertStringContainsString('98', $xml);
        $this->assertStringContainsString('96.5', $xml); // rata-rata tertimbang sks
        $this->assertStringContainsString("Sa'adatul Fitri", $xml);
        $this->assertStringContainsString('SURAT KETERANGAN', $xml);
    }

    public function test_letter_uses_grade_point_for_lama_scheme(): void
    {
        $admin = $this->makeAdmin();
        $student = Student::factory()->create(['nama' => 'Siti Aminah', 'no_induk' => '195020100002']);

        $this->approveSubmission($student, 'A30', 'lama', [
            'MAA62004' => [88.0, 'A', 4.0],
            'MAA61009' => [85.0, 'B+', 3.5],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.students.letter', $student));
        $xml = $this->extractDocumentXml($response->baseResponse->getFile()->getPathname());

        // Nilai Adendum PKS Lama = bobot (grade_point), bukan NA mentah.
        $this->assertStringContainsString('>4<', $xml);
        $this->assertStringContainsString('>3.5<', $xml);
        $this->assertStringNotContainsString('>88<', $xml);
        $this->assertStringNotContainsString('>85<', $xml);

        // Rata-rata tertimbang sks (4*3+3.5*3)/6 = 3.75
        $this->assertStringContainsString('3.75', $xml);
    }

    public function test_letter_includes_all_approved_modules_for_student(): void
    {
        $admin = $this->makeAdmin();
        $student = Student::factory()->create(['nama' => 'Stella', 'no_induk' => '225091000111008']);

        $this->approveSubmission($student, 'A30', 'lama', ['MAA62004' => [88, 'A', 4.0], 'MAA61009' => [85, 'A', 4.0]]);
        $this->approveSubmission($student, 'A40', 'lama', ['MAA62007' => [85, 'A', 4.0], 'MAA61022' => [85, 'A', 4.0]]);

        $response = $this->actingAs($admin)->get(route('admin.students.letter', $student));
        $xml = $this->extractDocumentXml($response->baseResponse->getFile()->getPathname());

        $this->assertStringContainsString('A30', $xml);
        $this->assertStringContainsString('A40', $xml);
    }

    public function test_returns_404_when_student_has_no_approved_submission(): void
    {
        $admin = $this->makeAdmin();
        $student = Student::factory()->create();

        $module = PaiModule::where('code', 'A10')->firstOrFail();
        Submission::create([
            'student_id' => $student->id,
            'pai_module_id' => $module->id,
            'scheme' => 'baru',
            'price' => 550000,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.students.letter', $student))
            ->assertNotFound();
    }

    public function test_download_button_only_shown_for_students_with_approved_submission(): void
    {
        $admin = $this->makeAdmin();
        $approvedStudent = Student::factory()->create(['nama' => 'Sudah Approved']);
        $pendingStudent = Student::factory()->create(['nama' => 'Belum Approved']);

        $this->approveSubmission($approvedStudent, 'A10', 'baru', ['MAA62043' => [90, 'A', 4.0], 'MAA61041' => [90, 'A', 4.0]]);

        $module = PaiModule::where('code', 'A20')->firstOrFail();
        Submission::create([
            'student_id' => $pendingStudent->id,
            'pai_module_id' => $module->id,
            'scheme' => 'baru',
            'price' => 550000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.students.index'));

        $response->assertSee(route('admin.students.letter', $approvedStudent), false);
        $response->assertDontSee(route('admin.students.letter', $pendingStudent), false);
    }

    public function test_non_admin_cannot_download_letter(): void
    {
        $student = Student::factory()->create();
        $this->approveSubmission($student, 'A10', 'baru', ['MAA62043' => [90, 'A', 4.0], 'MAA61041' => [90, 'A', 4.0]]);

        $this->actingAs($student->user)
            ->get(route('admin.students.letter', $student))
            ->assertForbidden();
    }
}
