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

    /**
     * Helper nilai. Default semester 'Genap 2223' (TA 22/23) = old-year → forceOldScheme.
     * Gunakan 'Genap 2425' (TA 24/25) untuk test jalur PKS Baru normal.
     */
    private function giveGrade(
        Student $student,
        string $courseCode,
        float $na,
        string $nh,
        string $semester = 'Genap 2223',
    ): CourseGrade {
        $course = Course::where('code', $courseCode)->firstOrFail();

        return CourseGrade::create([
            'course_id' => $course->id,
            'semester' => $semester,
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

    // -------------------------------------------------------------------------
    // Kasus 1: PKS Baru — HANYA berlaku kalau semua nilai dari TA 24/25 ke atas
    // -------------------------------------------------------------------------

    /**
     * Nilai dari TA 24/25 (baru) + lolos percentile → decision='baru'.
     */
    public function test_eligible_baru_when_na_meets_threshold_on_all_components(): void
    {
        $student = $this->makeStudent();
        $module = PaiModule::where('code', 'A10')->firstOrFail();

        $this->setThreshold('MAA62043', 80.0);
        $this->setThreshold('MAA61041', 80.0);
        // NH='C' (grade_point=2.0) → weighted avg=2.0 ≤ 3.5 → eligibleLama=false
        // NA ≥ threshold → eligibleBaru=true → decision='baru'
        $this->giveGrade($student, 'MAA62043', 85.0, 'C', 'Genap 2425');
        $this->giveGrade($student, 'MAA61041', 90.0, 'C', 'Ganjil 2425');

        $result = $this->service->evaluate($student, $module);

        $this->assertFalse($result->eligibleLama);
        $this->assertTrue($result->eligibleBaru);
        $this->assertSame('baru', $result->decision);
        $this->assertSame(550000, $result->price);
    }

    // -------------------------------------------------------------------------
    // Kasus 1b: Adendum PKS Lama DIUTAMAKAN — kalau lolos keduanya, pilih lama (lebih murah)
    // Rule a: ada matched set baru DAN lama → Adendum PKS Lama diutamakan
    // -------------------------------------------------------------------------

    /**
     * Mahasiswa punya nilai untuk kode kurikulum BARU (MAA62043+MAA61041) DAN LAMA
     * (MAA62009+MAA61015) sekaligus → kedua set matched.
     * NA ≥ threshold (eligibleBaru=true) DAN avg=4.0 > 3.5 (eligibleLama=true).
     * Adendum PKS Lama diutamakan → decision='lama' (Rp500.000, bukan Rp550.000).
     */
    public function test_lama_prioritized_when_both_curriculum_sets_are_matched(): void
    {
        $student = $this->makeStudent();
        $module = PaiModule::where('code', 'A10')->firstOrFail();

        // Set threshold untuk kursus baru supaya lolos PKS Baru juga
        $this->setThreshold('MAA62043', 80.0);
        $this->setThreshold('MAA61041', 80.0);

        // Nilai TA 24/25 → forceOldScheme=false
        $this->giveGrade($student, 'MAA62043', 90.0, 'A', 'Genap 2425'); // kode baru
        $this->giveGrade($student, 'MAA61041', 88.0, 'A', 'Ganjil 2425');
        $this->giveGrade($student, 'MAA62009', 85.0, 'A', 'Genap 2425'); // kode lama
        $this->giveGrade($student, 'MAA61015', 82.0, 'A', 'Ganjil 2425');

        $result = $this->service->evaluate($student, $module);

        // Kedua set matched (baru+lama) → rule (a) → Adendum PKS Lama diutamakan
        $this->assertTrue($result->eligibleBaru);  // NA 90,88 ≥ threshold 80 ✓
        $this->assertTrue($result->eligibleLama);  // avg=4.0 > 3.5 ✓
        $this->assertSame('lama', $result->decision);
        $this->assertSame(500000, $result->price);
    }

    // -------------------------------------------------------------------------
    // Kasus 2: Rule c — hanya kode kurikulum lama yang matched → tidak bisa disetarakan
    // -------------------------------------------------------------------------

    /**
     * Mahasiswa hanya punya nilai untuk kode kurikulum LAMA A10 (MAA62009+MAA61015).
     * Set baru (MAA62043+MAA61041) tidak ada nilainya → hanya lama yang matched.
     * Rule c: tidak ada 'baru' set → decision='none', walau weighted avg lolos sekalipun.
     */
    public function test_none_when_only_lama_curriculum_codes_matched(): void
    {
        $student = $this->makeStudent();
        $module = PaiModule::where('code', 'A10')->firstOrFail();

        // Kode lama saja — set baru (MAA62043+MAA61041) tidak punya nilai
        $this->giveGrade($student, 'MAA62009', 90.0, 'A'); // default 'Genap 2223'
        $this->giveGrade($student, 'MAA61015', 88.0, 'A');

        $result = $this->service->evaluate($student, $module);

        // Rule c: hanya lama set matched → tidak bisa disetarakan
        $this->assertFalse($result->eligibleLama);
        $this->assertFalse($result->eligibleBaru);
        $this->assertSame('none', $result->decision);
        $this->assertStringContainsString('kurikulum lama', $result->reason);
    }

    // -------------------------------------------------------------------------
    // Kasus 3: Adendum PKS Lama lolos dengan kode kurikulum baru + nilai BARU (≥ 24/25) → lama
    // Adendum PKS Lama berlaku untuk semua kode kurikulum (lama maupun baru).
    // -------------------------------------------------------------------------

    /**
     * A10 kurikulum baru + nilai TA 24/25: lolos 4b → Adendum PKS Lama (kode tidak diblokir).
     */
    public function test_lama_when_weighted_avg_passes_with_all_new_curriculum_codes(): void
    {
        $student = $this->makeStudent();
        $module = PaiModule::where('code', 'A10')->firstOrFail();

        // Tidak ada course_thresholds → percentile gagal otomatis.
        $this->giveGrade($student, 'MAA62043', 70.0, 'A', 'Genap 2425');
        $this->giveGrade($student, 'MAA61041', 70.0, 'A', 'Ganjil 2425');

        $result = $this->service->evaluate($student, $module);

        $this->assertFalse($result->eligibleBaru);
        $this->assertTrue($result->eligibleLama);
        $this->assertSame('lama', $result->decision); // kode baru tidak memblokir Adendum PKS Lama
        $this->assertSame(500000, $result->price);
    }

    // -------------------------------------------------------------------------
    // Kasus 4: komponen tidak lengkap
    // -------------------------------------------------------------------------

    public function test_none_when_components_are_incomplete(): void
    {
        $student = $this->makeStudent();
        $module = PaiModule::where('code', 'A10')->firstOrFail();

        $this->giveGrade($student, 'MAA62043', 90.0, 'A');

        $result = $this->service->evaluate($student, $module);

        $this->assertFalse($result->eligibleBaru);
        $this->assertFalse($result->eligibleLama);
        $this->assertSame('none', $result->decision);
        $this->assertStringContainsString('belum lulus semua matkul komponen', strtolower($result->reason));
    }

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

    // -------------------------------------------------------------------------
    // Kasus 5: weighted average tepat 3.5 → gagal Adendum PKS Lama (strictly greater)
    // -------------------------------------------------------------------------

    public function test_lama_fails_when_weighted_average_is_exactly_3_5(): void
    {
        $student = $this->makeStudent();
        $module = PaiModule::where('code', 'A10')->firstOrFail();

        // Kode BARU agar rule c tidak berlaku (baru set matched)
        // B++B+ → weighted avg = 3.5 (harus LEBIH DARI, bukan sama dengan)
        $this->giveGrade($student, 'MAA62043', 70.0, 'B+', 'Genap 2425');
        $this->giveGrade($student, 'MAA61041', 70.0, 'B+', 'Ganjil 2425');

        $result = $this->service->evaluate($student, $module);

        $this->assertFalse($result->eligibleBaru);
        $this->assertFalse($result->eligibleLama);
        $this->assertSame('none', $result->decision);
    }

    // -------------------------------------------------------------------------
    // Kasus 6: modul dengan kode matkul shared di dua kurikulum (A20)
    // -------------------------------------------------------------------------

    public function test_lama_decision_for_module_with_shared_curriculum_codes(): void
    {
        $student = $this->makeStudent();
        $module = PaiModule::where('code', 'A20')->firstOrFail();

        $this->giveGrade($student, 'MAA62003', 70.0, 'A');
        $this->giveGrade($student, 'MAA61007', 70.0, 'B+');

        $result = $this->service->evaluate($student, $module);

        $this->assertFalse($result->eligibleBaru);
        $this->assertTrue($result->eligibleLama);
        $this->assertSame('lama', $result->decision);
    }

    // -------------------------------------------------------------------------
    // Kasus 7: retake/duplikat → pakai NA tertinggi
    // -------------------------------------------------------------------------

    public function test_uses_highest_na_when_student_has_duplicate_grade_rows(): void
    {
        $student = $this->makeStudent();
        $module = PaiModule::where('code', 'A10')->firstOrFail();

        $this->setThreshold('MAA62043', 80.0);
        $this->setThreshold('MAA61041', 80.0);

        // Nilai terbaik dari TA 24/25 supaya forceOldScheme=false → PKS Baru bisa berlaku.
        $this->giveGrade($student, 'MAA62043', 60.0, 'C', 'Genap 2324'); // percobaan lama, NA rendah
        $this->giveGrade($student, 'MAA62043', 90.0, 'A', 'Genap 2425'); // retake baru, NA tinggi → dipakai
        $this->giveGrade($student, 'MAA61041', 85.0, 'A', 'Ganjil 2425');

        $result = $this->service->evaluate($student, $module);

        // NA=90 (grade terbaik) ≥ threshold=80 → eligibleBaru=true membuktikan NA tertinggi dipakai.
        // Adendum PKS Lama tetap menang (grade_point=4.0 → weighted avg > 3.5).
        $this->assertTrue($result->eligibleBaru);
        $this->assertSame('lama', $result->decision);
        $this->assertSame(500000, $result->price);
    }

    // -------------------------------------------------------------------------
    // Kasus 8: forceOldScheme — nilai TA 23/24 memblokir PKS Baru
    // (dikonfirmasi 2026-06-21)
    // -------------------------------------------------------------------------

    /**
     * Nilai dari TA 23/24 + lolos percentile → tetap Adendum PKS Lama (bukan PKS Baru).
     * A10 kurikulum baru (MAA62043 + MAA61041) dengan nilai A, threshold 80.
     */
    public function test_old_year_grade_forces_pks_lama_even_when_na_meets_threshold(): void
    {
        $student = $this->makeStudent();
        $module = PaiModule::where('code', 'A10')->firstOrFail();

        $this->setThreshold('MAA62043', 80.0);
        $this->setThreshold('MAA61041', 80.0);
        // Nilai dari TA 23/24 — otomatis forceOldScheme
        $this->giveGrade($student, 'MAA62043', 95.0, 'A', 'Genap 2324');
        $this->giveGrade($student, 'MAA61041', 92.0, 'A', 'Ganjil 2324');

        $result = $this->service->evaluate($student, $module);

        // Meski NA lolos percentile, PKS Baru diblokir oleh tahun lama.
        $this->assertFalse($result->eligibleBaru);
        $this->assertTrue($result->eligibleLama);
        $this->assertSame('lama', $result->decision);
        $this->assertSame(500000, $result->price);
        $this->assertStringContainsString('23/24', $result->reason);
    }

    /**
     * Campuran tahun: matkul 1 dari TA 23/24, matkul 2 dari TA 24/25
     * → cukup satu nilai lama untuk memblokir PKS Baru (rule "any").
     */
    public function test_mixed_years_any_old_grade_forces_pks_lama(): void
    {
        $student = $this->makeStudent();
        $module = PaiModule::where('code', 'A10')->firstOrFail();

        $this->setThreshold('MAA62043', 80.0);
        $this->setThreshold('MAA61041', 80.0);
        // Satu nilai TA 23/24, satu TA 24/25
        $this->giveGrade($student, 'MAA62043', 90.0, 'A', 'Genap 2324');
        $this->giveGrade($student, 'MAA61041', 88.0, 'A', 'Ganjil 2425');

        $result = $this->service->evaluate($student, $module);

        // "Any" old year → forceOldScheme aktif → PKS Baru diblokir
        $this->assertFalse($result->eligibleBaru);
        $this->assertSame('lama', $result->decision);
    }

    /**
     * Nilai TA 22/23 (lebih lama dari cutoff), kode kurikulum BARU, lolos 4b
     * → decision='lama' (kode kurikulum diabaikan karena forceOldScheme).
     *
     * Ini kebalikan dari Kasus 3: Kasus 3 pakai nilai baru (24/25) sehingga
     * kode kurikulum masih dicek. Di sini nilai lama → kode kurikulum tidak dicek.
     */
    public function test_old_year_forces_lama_even_for_new_curriculum_codes(): void
    {
        $student = $this->makeStudent();
        $module = PaiModule::where('code', 'A10')->firstOrFail();

        // MAA62043 + MAA61041 adalah kode kurikulum BARU.
        // Tidak ada threshold → percentile gagal kalau dievaluasi.
        $this->giveGrade($student, 'MAA62043', 70.0, 'A', 'Genap 2223'); // TA 22/23
        $this->giveGrade($student, 'MAA61041', 70.0, 'A', 'Ganjil 2223');

        $result = $this->service->evaluate($student, $module);

        // Sebelum aturan baru: decision='none' (kode baru, wajib PKS Baru).
        // Setelah aturan baru: decision='lama' (tahun lama override kode kurikulum).
        $this->assertFalse($result->eligibleBaru);
        $this->assertTrue($result->eligibleLama);
        $this->assertSame('lama', $result->decision);
        $this->assertSame(500000, $result->price);
    }

    // -------------------------------------------------------------------------
    // Kasus 9: S1 Matematika — hanya kursus MAM yang dievaluasi untuk A20
    // -------------------------------------------------------------------------

    /**
     * Mahasiswa S1 Matematika + nilai MAM60601 & MAM60602 (TA 24/25) + lolos 4b
     * → decision='lama' (satu kurikulum 'baru' untuk Matematika, walau lolos 4b
     * tidak ada kode 'lama' → ikut forceOldScheme=false path → none... TAPI
     * nilai TA 24/25 jadi forceOldScheme=false, dan curriculum='baru', jadi
     * decision='none' per decision tree 4c normal).
     *
     * Di sini kita pakai nilai TA 22/23 supaya forceOldScheme=true → decision='lama'.
     */
    public function test_matematika_student_evaluates_mam_courses_for_a20(): void
    {
        $user = User::factory()->create();
        $student = Student::create([
            'user_id' => $user->id,
            'no_induk' => '215080100001',
            'nama' => 'Mahasiswa Matematika',
            'prodi' => 'S1 Matematika',
        ]);

        $module = PaiModule::where('code', 'A20')->firstOrFail();

        // Nilai TA 22/23 → forceOldScheme=true → decision='lama' kalau 4b lolos
        $this->giveGrade($student, 'MAM60601', 85.0, 'A', 'Genap 2223');
        $this->giveGrade($student, 'MAM60602', 80.0, 'A', 'Genap 2223');

        $result = $this->service->evaluate($student, $module);

        $this->assertTrue($result->eligibleLama);
        $this->assertSame('lama', $result->decision);
    }

    /**
     * Mahasiswa S1 Matematika tidak boleh dievaluasi pakai kursus MAA (Aktuaria).
     * Jika hanya ada nilai MAA, module A20 untuk Matematika tetap 'none'.
     */
    public function test_matematika_student_is_not_matched_by_aktuaria_courses(): void
    {
        $user = User::factory()->create();
        $student = Student::create([
            'user_id' => $user->id,
            'no_induk' => '215080100002',
            'nama' => 'Mahasiswa Matematika 2',
            'prodi' => 'S1 Matematika',
        ]);

        $module = PaiModule::where('code', 'A20')->firstOrFail();

        // Nilai Aktuaria — tidak relevan untuk Matematika
        $this->giveGrade($student, 'MAA62003', 90.0, 'A', 'Genap 2425');
        $this->giveGrade($student, 'MAA61007', 90.0, 'A', 'Genap 2425');

        $result = $this->service->evaluate($student, $module);

        $this->assertSame('none', $result->decision);
    }

    // -------------------------------------------------------------------------
    // Kasus lain
    // -------------------------------------------------------------------------

    public function test_component_grades_are_included_for_admin_display(): void
    {
        $student = $this->makeStudent();
        $module = PaiModule::where('code', 'A10')->firstOrFail();

        $this->setThreshold('MAA62043', 80.0);
        $this->setThreshold('MAA61041', 80.0);
        // Nilai baru supaya PKS Baru bisa jalan
        $this->giveGrade($student, 'MAA62043', 85.0, 'A', 'Genap 2425');
        $this->giveGrade($student, 'MAA61041', 90.0, 'A', 'Ganjil 2425');

        $result = $this->service->evaluate($student, $module);

        $this->assertArrayHasKey('baru', $result->componentGrades);
        $this->assertCount(2, $result->componentGrades['baru']['courses']);
        $this->assertSame('MAA62043', $result->componentGrades['baru']['courses'][0]['course_code']);
    }

    // -------------------------------------------------------------------------
    // Kasus rule b: kombinasi CAMPURAN lama+baru (1 slot lama, 1 slot baru)
    // -------------------------------------------------------------------------

    /**
     * A10: MAA62009 (kode lama, slot "Matematika Finansial I") +
     *      MAA61041 (kode baru, slot "Matematika Finansial II").
     * Tidak ada satu set pun yang lengkap, tapi semua slot terisi via campuran.
     * Rule b → Adendum PKS Lama (PKS Baru tidak berlaku untuk campuran).
     */
    public function test_mixed_lama_baru_slots_eligible_for_adendum_pks_lama(): void
    {
        $student = $this->makeStudent();
        $module = PaiModule::where('code', 'A10')->firstOrFail();

        // Lama slot 1 (Matematika Finansial I versi lama)
        $this->giveGrade($student, 'MAA62009', 80.0, 'A'); // default 'Genap 2223'
        // Baru slot 2 (Matematika Finansial II versi baru)
        $this->giveGrade($student, 'MAA61041', 80.0, 'A');

        $result = $this->service->evaluate($student, $module);

        // Mixed → hanya bisa Adendum PKS Lama
        $this->assertSame('lama', $result->decision);
        $this->assertTrue($result->eligibleLama);   // avg 4.0 > 3.5
        $this->assertFalse($result->eligibleBaru);  // mixed tidak dievaluasi PKS Baru
        $this->assertSame(500000, $result->price);
    }

    /**
     * Mixed tapi weighted average tidak cukup (avg=2.0 ≤ 3.5) → none.
     */
    public function test_mixed_slots_none_when_weighted_avg_too_low(): void
    {
        $student = $this->makeStudent();
        $module = PaiModule::where('code', 'A10')->firstOrFail();

        $this->giveGrade($student, 'MAA62009', 80.0, 'C'); // grade_point=2.0
        $this->giveGrade($student, 'MAA61041', 80.0, 'C');

        $result = $this->service->evaluate($student, $module);

        $this->assertSame('none', $result->decision);
        $this->assertFalse($result->eligibleLama);
        $this->assertFalse($result->eligibleBaru);
    }
}
