<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseThreshold;
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

    public function test_admin_can_import_grades_and_threshold_gets_recomputed(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $course = Course::where('code', 'MAA62043')->firstOrFail();

        $csv = "No Induk,Nama,NA,NH\n".
            "195020100001,Andi,88.5,A\n".
            "195020100002,Budi,82.0,A\n".
            "195020100003,Citra,76.25,B+\n";

        $response = $this->actingAs($admin)->post(route('admin.grades.import.store'), [
            'course_id' => $course->id,
            'semester' => 'Genap 2223',
            'file' => $this->csvUploadedFile($csv),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseCount('course_grades', 3);
        $this->assertDatabaseHas('course_grades', [
            'course_id' => $course->id,
            'no_induk' => '195020100001',
            'nh' => 'A',
        ]);

        $threshold = CourseThreshold::where('course_id', $course->id)->first();
        $this->assertNotNull($threshold);
        $this->assertSame(80, (int) $threshold->percentile);
    }

    public function test_invalid_rows_are_skipped_and_reported_but_valid_rows_still_import(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $course = Course::where('code', 'MAA62043')->firstOrFail();

        $csv = "No Induk,Nama,NA,NH\n".
            "195020100001,Andi,88.5,A\n".
            "195020100002,Budi,150,A\n". // NA di luar 0-100, harus di-skip
            "195020100003,Citra,76.25,Z\n"; // NH tidak dikenal, harus di-skip

        $response = $this->actingAs($admin)->post(route('admin.grades.import.store'), [
            'course_id' => $course->id,
            'semester' => 'Genap 2223',
            'file' => $this->csvUploadedFile($csv),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('importErrors');

        $this->assertDatabaseCount('course_grades', 1);
        $this->assertDatabaseHas('course_grades', ['no_induk' => '195020100001']);
    }

    public function test_corrupt_file_returns_friendly_error_instead_of_500(): void
    {
        // mimes validation Laravel sudah cukup ketat soal magic bytes, jadi
        // file "rusak" asli susah lolos sampai ke controller buat nguji
        // try/catch-nya. Mock Excel::import() supaya melempar exception
        // persis seperti kalau PhpSpreadsheet gagal parse file yang valid
        // secara mimes tapi corrupt secara struktur internal.
        \Maatwebsite\Excel\Facades\Excel::shouldReceive('import')
            ->once()
            ->andThrow(new \Exception('Simulated corrupt spreadsheet.'));

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $course = Course::where('code', 'MAA62043')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('admin.grades.import.store'), [
            'course_id' => $course->id,
            'semester' => 'Genap 2223',
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
