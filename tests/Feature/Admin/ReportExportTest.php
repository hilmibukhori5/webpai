<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseGrade;
use App\Models\PaiModule;
use App\Models\Student;
use App\Models\Submission;
use App\Models\SubmissionCourse;
use App\Models\User;
use Database\Seeders\CourseSeeder;
use Database\Seeders\ModuleCourseSeeder;
use Database\Seeders\PaiModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ReportExportTest extends TestCase
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

    /**
     * Submission approved, scheme "lama", modul A30 (Ekonomi), 2 matkul komponen.
     */
    private function makeLamaSubmission(string $nama, string $noInduk): Submission
    {
        $student = Student::factory()->create(['nama' => $nama, 'no_induk' => $noInduk]);
        $module = PaiModule::where('code', 'A30')->firstOrFail();
        $courseA = Course::where('code', 'MAA62004')->firstOrFail();
        $courseB = Course::where('code', 'MAA61009')->firstOrFail();

        CourseGrade::create(['course_id' => $courseA->id, 'semester' => 'Genap/2022', 'no_induk' => $noInduk, 'nama' => $nama, 'na' => 88, 'nh' => 'A', 'grade_point' => 4.0]);
        CourseGrade::create(['course_id' => $courseB->id, 'semester' => 'Ganjil/2023', 'no_induk' => $noInduk, 'nama' => $nama, 'na' => 85, 'nh' => 'A', 'grade_point' => 4.0]);

        $submission = Submission::create([
            'student_id' => $student->id,
            'pai_module_id' => $module->id,
            'scheme' => 'lama',
            'price' => 500000,
            'status' => 'approved',
        ]);

        SubmissionCourse::create(['submission_id' => $submission->id, 'course_id' => $courseA->id, 'na' => 88, 'nh' => 'A', 'grade_point' => 4.0]);
        SubmissionCourse::create(['submission_id' => $submission->id, 'course_id' => $courseB->id, 'na' => 85, 'nh' => 'A', 'grade_point' => 4.0]);

        return $submission;
    }

    /**
     * Submission approved, scheme "baru", modul A10 (Matematika Keuangan).
     */
    private function makeBaruSubmission(string $nama, string $noInduk): Submission
    {
        $student = Student::factory()->create(['nama' => $nama, 'no_induk' => $noInduk]);
        $module = PaiModule::where('code', 'A10')->firstOrFail();
        $courseA = Course::where('code', 'MAA62043')->firstOrFail();
        $courseB = Course::where('code', 'MAA61041')->firstOrFail();

        CourseGrade::create(['course_id' => $courseA->id, 'semester' => 'Genap/2023', 'no_induk' => $noInduk, 'nama' => $nama, 'na' => 90.5, 'nh' => 'A', 'grade_point' => 4.0]);
        CourseGrade::create(['course_id' => $courseB->id, 'semester' => 'Ganjil/2024', 'no_induk' => $noInduk, 'nama' => $nama, 'na' => 83.2, 'nh' => 'A', 'grade_point' => 4.0]);

        $submission = Submission::create([
            'student_id' => $student->id,
            'pai_module_id' => $module->id,
            'scheme' => 'baru',
            'price' => 550000,
            'status' => 'approved',
        ]);

        SubmissionCourse::create(['submission_id' => $submission->id, 'course_id' => $courseA->id, 'na' => 90.5, 'nh' => 'A', 'grade_point' => 4.0]);
        SubmissionCourse::create(['submission_id' => $submission->id, 'course_id' => $courseB->id, 'na' => 83.2, 'nh' => 'A', 'grade_point' => 4.0]);

        return $submission;
    }

    public function test_admin_sees_report_index_with_counts(): void
    {
        $admin = $this->makeAdmin();
        $this->makeLamaSubmission('Stella', '225091000111008');

        $response = $this->actingAs($admin)->get(route('admin.reports.index'));

        $response->assertOk();
        $response->assertSee('1', false); // approvedLamaCount
    }

    public function test_lama_export_contains_nh_values_and_correct_header(): void
    {
        $admin = $this->makeAdmin();
        $this->makeLamaSubmission('Stella Paulina Gadis Ginanda', '225091000111008');

        $response = $this->actingAs($admin)->get(route('admin.reports.export', 'lama'));

        $response->assertOk();

        $sheet = IOFactory::load($response->baseResponse->getFile()->getPathname())->getActiveSheet();

        $this->assertSame('No', $sheet->getCell('A1')->getValue());
        $this->assertSame('Modul PAI', $sheet->getCell('E1')->getValue());
        $this->assertSame('A30', $sheet->getCell('G2')->getValue()); // E,F,G = A10,A20,A30
        $this->assertSame('Mata Kuliah Disetarakan', $sheet->getCell('L1')->getValue());
        $this->assertSame('Kode', $sheet->getCell('L2')->getValue());
        $this->assertSame('Nilai', $sheet->getCell('M2')->getValue());

        // Baris data pertama.
        $this->assertSame(1, $sheet->getCell('A3')->getValue());
        $this->assertSame('225091000111008', $sheet->getCell('C3')->getValue());
        $this->assertSame('Stella Paulina Gadis Ginanda', $sheet->getCell('D3')->getValue());
        $this->assertSame(1, $sheet->getCell('G3')->getValue()); // one-hot A30
        $this->assertSame('MAA62004', $sheet->getCell('L3')->getValue());
        $this->assertSame('A', $sheet->getCell('M3')->getValue()); // PKS Lama -> NH
        $this->assertSame('Genap/2022', $sheet->getCell('N3')->getValue());
        $this->assertSame('MAA61009', $sheet->getCell('O3')->getValue());
        $this->assertSame('A', $sheet->getCell('P3')->getValue());
        $this->assertSame('Ganjil/2023', $sheet->getCell('Q3')->getValue());
    }

    public function test_baru_export_contains_numeric_na_values(): void
    {
        $admin = $this->makeAdmin();
        $this->makeBaruSubmission('Vivi Anggraeny', '215091001111010');

        $response = $this->actingAs($admin)->get(route('admin.reports.export', 'baru'));

        $response->assertOk();

        $sheet = IOFactory::load($response->baseResponse->getFile()->getPathname())->getActiveSheet();

        $this->assertSame('215091001111010', $sheet->getCell('C3')->getValue());
        $this->assertEqualsWithDelta(90.5, (float) $sheet->getCell('M3')->getValue(), 0.001);
        $this->assertEqualsWithDelta(83.2, (float) $sheet->getCell('P3')->getValue(), 0.001);
    }

    public function test_export_only_includes_approved_submissions_of_matching_scheme(): void
    {
        $admin = $this->makeAdmin();
        $this->makeLamaSubmission('Stella', '225091000111008');
        $baru = $this->makeBaruSubmission('Vivi', '215091001111010');
        $baru->update(['status' => 'pending']); // jangan ikut laporan

        $response = $this->actingAs($admin)->get(route('admin.reports.export', 'baru'));
        $sheet = IOFactory::load($response->baseResponse->getFile()->getPathname())->getActiveSheet();

        // Tidak ada baris data karena satu-satunya submission scheme=baru masih pending.
        $this->assertNull($sheet->getCell('A3')->getValue());
    }

    public function test_export_groups_use_max_components_across_rows(): void
    {
        $admin = $this->makeAdmin();
        $this->makeLamaSubmission('Stella', '225091000111008');

        // A50 punya 4 matkul komponen (kurikulum lama) -> bikin baris dengan 4 grup.
        $student = Student::factory()->create(['nama' => 'Vivi Anggraeny', 'no_induk' => '215091001111010']);
        $module = PaiModule::where('code', 'A50')->firstOrFail();
        $courses = Course::whereIn('code', ['MAA62011', 'MAA61016', 'MAA62023', 'MAA62013'])->get()->keyBy('code');

        $submission = Submission::create([
            'student_id' => $student->id,
            'pai_module_id' => $module->id,
            'scheme' => 'lama',
            'price' => 500000,
            'status' => 'approved',
        ]);

        foreach (['MAA62011', 'MAA61016', 'MAA62023', 'MAA62013'] as $code) {
            CourseGrade::create(['course_id' => $courses[$code]->id, 'semester' => 'Genap/2022', 'no_induk' => '215091001111010', 'nama' => 'Vivi Anggraeny', 'na' => 80, 'nh' => 'B+', 'grade_point' => 3.5]);
            SubmissionCourse::create(['submission_id' => $submission->id, 'course_id' => $courses[$code]->id, 'na' => 80, 'nh' => 'B+', 'grade_point' => 3.5]);
        }

        $response = $this->actingAs($admin)->get(route('admin.reports.export', 'lama'));
        $sheet = IOFactory::load($response->baseResponse->getFile()->getPathname())->getActiveSheet();

        // Header harus punya 4 grup (12 kolom) -> kolom terakhir = L + 12 - 1 = W.
        $this->assertSame('Kode', $sheet->getCell('U2')->getValue()); // grup ke-4 dimulai di U
        $this->assertSame('MAA62013', $sheet->getCell('U4')->getValue());
    }

    public function test_non_admin_cannot_access_reports(): void
    {
        $student = Student::factory()->create();

        $this->actingAs($student->user)->get(route('admin.reports.index'))->assertForbidden();
        $this->actingAs($student->user)->get(route('admin.reports.export', 'lama'))->assertForbidden();
    }

    public function test_invalid_scheme_returns_404(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get('/admin/reports/export/invalid')->assertNotFound();
    }
}
