<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseGrade;
use App\Models\CourseThreshold;
use App\Models\GradeUploadStatus;
use App\Models\User;
use Database\Seeders\CourseSeeder;
use Database\Seeders\ModuleCourseSeeder;
use Database\Seeders\PaiModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class GradeImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PaiModuleSeeder::class, CourseSeeder::class, ModuleCourseSeeder::class]);
    }

    private function csvUploadedFile(string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'grades').'.csv';
        file_put_contents($path, $content);

        return new UploadedFile($path, 'grades.csv', 'text/csv', null, true);
    }

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    public function test_admin_can_import_grades_and_threshold_gets_recomputed(): void
    {
        $admin = $this->makeAdmin();
        $course = Course::where('code', 'MAA62043')->firstOrFail();

        $csv = "No Induk,Nama,NA,NH\n".
            "195020100001,Andi,88.5,A\n".
            "195020100002,Budi,82.0,A\n".
            "195020100003,Citra,76.25,B+\n";

        $response = $this->actingAs($admin)->post(route('admin.grades.import.store'), [
            'course_id' => $course->id,
            'year' => '2223',
            'file' => $this->csvUploadedFile($csv),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseCount('course_grades', 3);
        $this->assertDatabaseHas('course_grades', [
            'course_id' => $course->id,
            'semester' => 'Genap 2223',
            'no_induk' => '195020100001',
            'nh' => 'A',
        ]);

        $threshold = CourseThreshold::where('course_id', $course->id)->first();
        $this->assertNotNull($threshold);
        $this->assertSame(80, (int) $threshold->percentile);
    }

    public function test_reimport_replaces_existing_data(): void
    {
        $admin = $this->makeAdmin();
        $course = Course::where('code', 'MAA62043')->firstOrFail();

        $csv1 = "No Induk,Nama,NA,NH\n195020100001,Andi,88.5,A\n";
        $csv2 = "No Induk,Nama,NA,NH\n195020100099,Baru,91.0,A\n195020100098,Baru2,85.0,B+\n";

        $this->actingAs($admin)->post(route('admin.grades.import.store'), [
            'course_id' => $course->id, 'year' => '2324', 'file' => $this->csvUploadedFile($csv1),
        ]);

        $this->assertDatabaseCount('course_grades', 1);

        $this->actingAs($admin)->post(route('admin.grades.import.store'), [
            'course_id' => $course->id, 'year' => '2324', 'file' => $this->csvUploadedFile($csv2),
        ]);

        // Data lama (195020100001) dihapus, diganti 2 baris baru
        $this->assertDatabaseCount('course_grades', 2);
        $this->assertDatabaseMissing('course_grades', ['no_induk' => '195020100001']);
        $this->assertDatabaseHas('course_grades', ['no_induk' => '195020100099']);
    }

    public function test_admin_can_skip_a_period_with_note(): void
    {
        $admin = $this->makeAdmin();
        $course = Course::where('code', 'MAA62043')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('admin.grades.import.skip'), [
            'course_id' => $course->id,
            'period' => 'Genap 2021',
            'note' => 'Matkul tidak berjalan semester ini.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('grade_upload_statuses', [
            'course_id' => $course->id,
            'period' => 'Genap 2021',
            'note' => 'Matkul tidak berjalan semester ini.',
        ]);
    }

    public function test_uploading_removes_skip_status(): void
    {
        $admin = $this->makeAdmin();
        $course = Course::where('code', 'MAA62043')->firstOrFail();

        GradeUploadStatus::create(['course_id' => $course->id, 'period' => 'Genap 2324', 'note' => 'skip dulu']);

        $this->actingAs($admin)->post(route('admin.grades.import.store'), [
            'course_id' => $course->id,
            'year' => '2324',
            'file' => $this->csvUploadedFile("No Induk,Nama,NA,NH\n195020100001,Andi,88.5,A\n"),
        ]);

        $this->assertDatabaseMissing('grade_upload_statuses', [
            'course_id' => $course->id,
            'period' => 'Genap 2324',
        ]);
    }

    public function test_admin_can_unskip_a_period(): void
    {
        $admin = $this->makeAdmin();
        $course = Course::where('code', 'MAA62043')->firstOrFail();
        $status = GradeUploadStatus::create(['course_id' => $course->id, 'period' => 'Genap 2324']);

        $response = $this->actingAs($admin)->delete(route('admin.grades.import.unskip', $status));

        $response->assertRedirect();
        $this->assertDatabaseMissing('grade_upload_statuses', ['id' => $status->id]);
    }

    public function test_invalid_rows_are_skipped_and_reported_but_valid_rows_still_import(): void
    {
        $admin = $this->makeAdmin();
        $course = Course::where('code', 'MAA62043')->firstOrFail();

        $csv = "No Induk,Nama,NA,NH\n".
            "195020100001,Andi,88.5,A\n".
            "195020100002,Budi,150,A\n". // NA di luar 0-100
            "195020100003,Citra,76.25,Z\n"; // NH tidak dikenal

        $response = $this->actingAs($admin)->post(route('admin.grades.import.store'), [
            'course_id' => $course->id,
            'year' => '2223',
            'file' => $this->csvUploadedFile($csv),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('importErrors');

        $this->assertDatabaseCount('course_grades', 1);
        $this->assertDatabaseHas('course_grades', ['no_induk' => '195020100001']);
    }

    public function test_corrupt_file_returns_friendly_error_instead_of_500(): void
    {
        \Maatwebsite\Excel\Facades\Excel::shouldReceive('import')
            ->once()
            ->andThrow(new \Exception('Simulated corrupt spreadsheet.'));

        $admin = $this->makeAdmin();
        $course = Course::where('code', 'MAA62043')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('admin.grades.import.store'), [
            'course_id' => $course->id,
            'year' => '2223',
            'file' => $this->csvUploadedFile("No Induk,Nama,NA,NH\n195020100001,Andi,88.5,A\n"),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('course_grades', 0);
    }

    public function test_non_admin_cannot_access_import_form(): void
    {
        $student = User::factory()->create(['role' => UserRole::Student]);

        $this->actingAs($student)
            ->get(route('admin.grades.import.create'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.grades.import.create'))
            ->assertRedirect(route('login'));
    }
}
