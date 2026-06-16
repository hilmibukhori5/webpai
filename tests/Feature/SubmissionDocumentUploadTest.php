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
 * Upload bukti bayar + formulir terisi (di luar 8 fase asli spec, ditambah
 * belakangan). Status pembayaran otomatis "paid" begitu KEDUA file ada,
 * tanpa langkah verifikasi admin (dikonfirmasi user).
 */
class SubmissionDocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PaiModuleSeeder::class);
    }

    private function makeApprovedSubmission(): Submission
    {
        $student = Student::factory()->create();
        $module = PaiModule::firstOrFail();

        return Submission::create([
            'student_id' => $student->id,
            'pai_module_id' => $module->id,
            'scheme' => 'baru',
            'price' => 550000,
            'status' => 'approved',
        ]);
    }

    public function test_uploading_both_files_marks_submission_as_paid(): void
    {
        Storage::fake('public');
        $submission = $this->makeApprovedSubmission();
        $student = $submission->student;

        $response = $this->actingAs($student->user)->post(route('submissions.documents.update', $submission), [
            'bukti_pembayaran' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
            'formulir_terisi' => UploadedFile::fake()->create('formulir.docx', 100),
        ]);

        $response->assertRedirect();
        $submission->refresh();

        $this->assertSame('paid', $submission->payment_status);
        $this->assertNotNull($submission->bukti_pembayaran_path);
        $this->assertNotNull($submission->formulir_terisi_path);
        Storage::disk('public')->assertExists($submission->bukti_pembayaran_path);
        Storage::disk('public')->assertExists($submission->formulir_terisi_path);
    }

    public function test_uploading_only_one_file_keeps_unpaid(): void
    {
        Storage::fake('public');
        $submission = $this->makeApprovedSubmission();
        $student = $submission->student;

        $this->actingAs($student->user)->post(route('submissions.documents.update', $submission), [
            'bukti_pembayaran' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
        ]);

        $submission->refresh();

        $this->assertSame('unpaid', $submission->payment_status);
        $this->assertNotNull($submission->bukti_pembayaran_path);
        $this->assertNull($submission->formulir_terisi_path);
    }

    public function test_non_owner_student_cannot_upload(): void
    {
        Storage::fake('public');
        $submission = $this->makeApprovedSubmission();
        $otherStudent = Student::factory()->create();

        $response = $this->actingAs($otherStudent->user)->post(route('submissions.documents.update', $submission), [
            'bukti_pembayaran' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
        ]);

        $response->assertForbidden();
        $this->assertSame('unpaid', $submission->refresh()->payment_status);
    }

    public function test_cannot_upload_before_submission_is_approved(): void
    {
        Storage::fake('public');
        $submission = $this->makeApprovedSubmission();
        $submission->update(['status' => 'pending']);
        $student = $submission->student;

        $response = $this->actingAs($student->user)->post(route('submissions.documents.update', $submission), [
            'bukti_pembayaran' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
        ]);

        $response->assertForbidden();
    }
}
