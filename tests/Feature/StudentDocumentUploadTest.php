<?php

namespace Tests\Feature;

use App\Models\PaiModule;
use App\Models\Student;
use App\Models\Submission;
use Database\Seeders\PaiModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Upload bukti bayar + formulir terisi per mahasiswa (bukan per submission).
 * Status pembayaran otomatis "paid" begitu KEDUA file ada.
 */
class StudentDocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PaiModuleSeeder::class);
    }

    private function makeStudentWithApprovedSubmission(): Student
    {
        $student = Student::factory()->create();
        $module = PaiModule::firstOrFail();

        Submission::create([
            'student_id' => $student->id,
            'pai_module_id' => $module->id,
            'scheme' => 'baru',
            'price' => 550000,
            'status' => 'approved',
        ]);

        return $student;
    }

    public function test_uploading_both_files_marks_student_as_paid(): void
    {
        Storage::fake('public');
        $student = $this->makeStudentWithApprovedSubmission();

        $response = $this->actingAs($student->user)->post(route('student.documents.update'), [
            'bukti_pembayaran' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
            'formulir_terisi' => UploadedFile::fake()->create('formulir.docx', 100),
        ]);

        $response->assertRedirect();
        $student->refresh();

        $this->assertSame('paid', $student->payment_status);
        $this->assertNotNull($student->bukti_pembayaran_path);
        $this->assertNotNull($student->formulir_terisi_path);
        Storage::disk('public')->assertExists($student->bukti_pembayaran_path);
        Storage::disk('public')->assertExists($student->formulir_terisi_path);
    }

    public function test_uploading_only_one_file_keeps_unpaid(): void
    {
        Storage::fake('public');
        $student = $this->makeStudentWithApprovedSubmission();

        $this->actingAs($student->user)->post(route('student.documents.update'), [
            'bukti_pembayaran' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
        ]);

        $student->refresh();

        $this->assertSame('unpaid', $student->payment_status);
        $this->assertNotNull($student->bukti_pembayaran_path);
        $this->assertNull($student->formulir_terisi_path);
    }

    public function test_student_without_approved_submission_cannot_upload(): void
    {
        Storage::fake('public');
        $student = Student::factory()->create();
        $module = PaiModule::firstOrFail();

        Submission::create([
            'student_id' => $student->id,
            'pai_module_id' => $module->id,
            'scheme' => 'baru',
            'price' => 550000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($student->user)->post(route('student.documents.update'), [
            'bukti_pembayaran' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
        ]);

        $response->assertForbidden();
        $this->assertSame('unpaid', $student->refresh()->payment_status);
    }

    public function test_other_student_cannot_access_upload_page(): void
    {
        $this->makeStudentWithApprovedSubmission();
        $otherStudent = Student::factory()->create();

        // Student lain tanpa approved submission tidak bisa akses halaman upload
        $response = $this->actingAs($otherStudent->user)->get(route('student.documents.edit'));

        $response->assertForbidden();
    }
}
