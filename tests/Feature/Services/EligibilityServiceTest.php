<?php

namespace Tests\Feature\Services;

use App\Models\Course;
use App\Models\CourseGrade;
use App\Models\CourseThreshold;
use App\Models\PaiModule;
use App\Models\Student;
use App\Models\User;
use App\Services\EligibilityService;
use Database\Seeders\CourseSeeder;
use Database\Seeders\ModuleCourseSeeder;
use Database\Seeders\PaiModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EligibilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private EligibilityService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PaiModuleSeeder::class, CourseSeeder::class, ModuleCourseSeeder::class]);

        $this->service = new EligibilityService;
    }

    private function makeStudent(string $noInduk = '195020100001'): Student
    {
        $user = User::factory()->create();

        return Student::create([
            'user_id' => $user->id,
            'no_induk' => $noInduk,
            'nama' => 'Mahasiswa Uji',
            'prodi' => 'S1 Ilmu Aktuaria',
        ]);
    }

    private function giveGrade(Student $student, string $courseCode, float $na, string $nh): CourseGrade
    {
        $course = Course::where('code', $courseCode)->firstOrFail();

        return CourseGrade::create([
            'course_id' => $course->id,
            'semester' => 'Genap 2223',
            'no_induk' => $student->no_induk,
            'nama' => $student->nama,
            'na' => $na,
            'nh' => $nh,
            'grade_point' => \App\Support\GradeScale::toWeight($nh),
        ]);
    }

    private function setThreshold(string $courseCode, float $thresholdNa, float $percentile = 80): void
    {
        $course = Course::where('code', $courseCode)->firstOrFail();

        CourseThreshold::create([
            'course_id' => $course->id,
            'percentile' => $percentile,
            'threshold_na' => $thresholdNa,
            'computed_at' => now(),
        ]);
    }

    /**
     * Kasus 1: eligible PKS Baru.
     *
     * A10 kurikulum baru = MAA62043 (3 sks), MAA61041 (3 sks).
     */
    public function test_eligible_baru_when_na_meets_threshold_on_all_components(): void
    {
        $student = $this->makeStudent();
        $module = PaiModule::where('code', 'A10')->firstOrFail();

        $this->setThreshold('MAA62043', 80.0);
        $this->setThreshold('MAA61041', 80.0);
        $this->giveGrade($student, 'MAA62043', 85.0, 'A');
        $this->giveGrade($student, 'MAA61041', 90.0, 'A');

        $result = $this->service->evaluate($student, $module);

        $this->assertTrue($result->eligibleBaru);
        $this->assertSame('baru', $result->decision);
        $this->assertSame(550000, $result->price);
    }

    /**
     * Kasus 2: eligible PKS Lama, matkul yang di-match berkode kurikulum lama.
     *
     * A10 kurikulum lama = MAA62009 (3 sks), MAA61015 (3 sks).
     * A(4.0,3) + B+(3.5,3) = (12+10.5)/6 = 3.75 > 3.5.
     */
    public function test_eligible_lama_when_matched_courses_are_old_curriculum(): void
    {
        $student = $this->makeStudent();
        $module = PaiModule::where('code', 'A10')->firstOrFail();

        // Sengaja tidak bikin course_thresholds -> percentile (4a) otomatis gagal,
        // supaya hasil benar-benar lewat jalur PKS Lama (4b), bukan ketolong baru.
        $this->giveGrade($student, 'MAA62009', 70.0, 'A');
        $this->giveGrade($student, 'MAA61015', 70.0, 'B+');

        $result = $this->service->evaluate($student, $module);

        $this->assertFalse($result->eligibleBaru);
        $this->assertTrue($result->eligibleLama);
        $this->assertSame('lama', $result->decision);
        $this->assertSame(500000, $result->price);
    }

    /**
     * Kasus 3: lolos syarat PKS Lama tapi semua matkul yang di-match berkode
     * kurikulum baru -> decision tetap "none" (bukan celah kurikulum baru).
     *
     * A10 kurikulum baru = MAA62043 (3 sks), MAA61041 (3 sks).
     * A(4.0,3) + A(4.0,3) = 4.0 > 3.5, tapi semua kode baru.
     */
    public function test_none_when_lama_passes_but_all_matched_courses_are_new_curriculum(): void
    {
        $student = $this->makeStudent();
        $module = PaiModule::where('code', 'A10')->firstOrFail();

        // Tidak ada course_thresholds -> percentile gagal otomatis.
        $this->giveGrade($student, 'MAA62043', 70.0, 'A');
        $this->giveGrade($student, 'MAA61041', 70.0, 'A');

        $result = $this->service->evaluate($student, $module);

        $this->assertFalse($result->eligibleBaru);
        $this->assertTrue($result->eligibleLama); // syarat 4b lolos secara matematis...
        $this->assertSame('none', $result->decision); // ...tapi decision tetap none
        $this->assertNull($result->price);
    }

    /**
     * Kasus 4: matkul komponen belum lengkap (salah satu belum diambil).
     */
    public function test_none_when_components_are_incomplete(): void
    {
        $student = $this->makeStudent();
        $module = PaiModule::where('code', 'A10')->firstOrFail();

        // Cuma kasih nilai 1 dari 2 matkul komponen kurikulum baru, dan tidak
        // sama sekali untuk kurikulum lama -> tidak ada curriculum yang lengkap.
        $this->giveGrade($student, 'MAA62043', 90.0, 'A');

        $result = $this->service->evaluate($student, $module);

        $this->assertFalse($result->eligibleBaru);
        $this->assertFalse($result->eligibleLama);
        $this->assertSame('none', $result->decision);
        $this->assertStringContainsString('belum lulus semua matkul komponen', strtolower($result->reason));
    }

    /**
     * Kasus 4b: matkul komponen "lengkap" tapi salah satu nilainya E -> tetap
     * dianggap belum lulus semua matkul (bukan dievaluasi sebagai gagal biasa).
     */
    public function test_none_when_one_component_has_failing_grade_e(): void
    {
        $student = $this->makeStudent();
        $module = PaiModule::where('code', 'A10')->firstOrFail();

        $this->giveGrade($student, 'MAA62043', 90.0, 'A');
        $this->giveGrade($student, 'MAA61041', 40.0, 'E');

        $result = $this->service->evaluate($student, $module);

        $this->assertSame('none', $result->decision);
        $this->assertStringContainsString('belum lulus semua matkul komponen', strtolower($result->reason));
    }

    /**
     * Kasus 5: rata-rata bobot tertimbang SKS pas 3.5 -> gagal PKS Lama
     * (aturan strictly greater than, bukan >=). Contoh persis dari spec
     * bagian 4b: B+ + B+ = 3.5 -> tidak eligible.
     */
    public function test_lama_fails_when_weighted_average_is_exactly_3_5(): void
    {
        $student = $this->makeStudent();
        $module = PaiModule::where('code', 'A10')->firstOrFail();

        $this->giveGrade($student, 'MAA62009', 70.0, 'B+');
        $this->giveGrade($student, 'MAA61015', 70.0, 'B+');

        $result = $this->service->evaluate($student, $module);

        $this->assertFalse($result->eligibleBaru);
        $this->assertFalse($result->eligibleLama);
        $this->assertSame('none', $result->decision);
    }

    /**
     * Kasus tambahan: modul dengan kode matkul SHARED di kedua kurikulum
     * (A20: MAA62003/MAA61007 sama-sama dipakai baru & lama). Begitu lolos
     * PKS Lama, "matched courses" otomatis mengandung tag kurikulum lama
     * juga (karena course yang sama terdaftar di 2 baris module_course).
     */
    public function test_lama_decision_for_module_with_shared_curriculum_codes(): void
    {
        $student = $this->makeStudent();
        $module = PaiModule::where('code', 'A20')->firstOrFail();

        // Tidak bikin course_thresholds -> percentile gagal otomatis.
        $this->giveGrade($student, 'MAA62003', 70.0, 'A');
        $this->giveGrade($student, 'MAA61007', 70.0, 'B+');

        $result = $this->service->evaluate($student, $module);

        $this->assertFalse($result->eligibleBaru);
        $this->assertTrue($result->eligibleLama);
        $this->assertSame('lama', $result->decision);
    }

    /**
     * Kasus tambahan: retake/duplikat nilai -> pakai NA tertinggi.
     */
    public function test_uses_highest_na_when_student_has_duplicate_grade_rows(): void
    {
        $student = $this->makeStudent();
        $module = PaiModule::where('code', 'A10')->firstOrFail();

        $this->setThreshold('MAA62043', 80.0);
        $this->setThreshold('MAA61041', 80.0);

        // Percobaan pertama gagal threshold, percobaan ke-2 (retake) lolos.
        $this->giveGrade($student, 'MAA62043', 60.0, 'C');
        $this->giveGrade($student, 'MAA62043', 90.0, 'A');
        $this->giveGrade($student, 'MAA61041', 85.0, 'A');

        $result = $this->service->evaluate($student, $module);

        $this->assertTrue($result->eligibleBaru);
        $this->assertSame('baru', $result->decision);
    }

    public function test_component_grades_are_included_for_admin_display(): void
    {
        $student = $this->makeStudent();
        $module = PaiModule::where('code', 'A10')->firstOrFail();

        $this->setThreshold('MAA62043', 80.0);
        $this->setThreshold('MAA61041', 80.0);
        $this->giveGrade($student, 'MAA62043', 85.0, 'A');
        $this->giveGrade($student, 'MAA61041', 90.0, 'A');

        $result = $this->service->evaluate($student, $module);

        $this->assertArrayHasKey('baru', $result->componentGrades);
        $this->assertCount(2, $result->componentGrades['baru']['courses']);
        $this->assertSame('MAA62043', $result->componentGrades['baru']['courses'][0]['course_code']);
    }
}
