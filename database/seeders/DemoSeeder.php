<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseGrade;
use App\Models\PaiModule;
use App\Models\Student;
use App\Models\Submission;
use App\Models\SubmissionCourse;
use App\Models\User;
use App\Services\EligibilityService;
use App\Services\ThresholdService;
use App\Support\GradeScale;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder demo (docs/spec.md bagian 8 Fase 7): 1 admin + beberapa mahasiswa
 * dengan nilai contoh yang mengaktifkan TIAP cabang decision tree (bagian 4c).
 *
 * Jalankan terpisah dari seeder master (TIDAK ikut DatabaseSeeder default):
 *   php artisan db:seed --class=DemoSeeder
 *
 * Wajib jalan SETELAH PaiModuleSeeder/CourseSeeder/ModuleCourseSeeder
 * (migrate:fresh --seed sudah otomatis menjalankan ketiganya).
 */
class DemoSeeder extends Seeder
{
    private EligibilityService $eligibility;

    private ThresholdService $thresholds;

    public function run(): void
    {
        $this->eligibility = app(EligibilityService::class);
        $this->thresholds = app(ThresholdService::class);

        $admin = $this->createAdmin();
        $this->seedThresholdPools();

        // 1) Eligible PKS Baru (NH=C → avg≤3.5, NA≥threshold=91) -> sudah disetujui admin.
        $ahmad = $this->createStudent('195020100001', 'Ahmad Fauzi', 'S1 Ilmu Aktuaria');
        $this->giveGrade($ahmad, 'MAA62043', 95.0, 'C', 'Genap 2425'); // gp=2.0 → eligibleLama=false
        $this->giveGrade($ahmad, 'MAA61041', 98.0, 'C', 'Ganjil 2425'); // NA≥91 → eligibleBaru=true
        $this->submitAndReview($ahmad, 'A10', 'approved', $admin);

        // 2) Eligible Adendum PKS Lama — kode baru A30, nilai lama (forceOldScheme), avg A+A=4.0>3.5.
        $siti = $this->createStudent('195020100002', 'Siti Aminah', 'S1 Ilmu Aktuaria');
        $this->giveGrade($siti, 'MAA62004', 70.0, 'A');  // A30 baru — Pengantar Ekonomi Mikro
        $this->giveGrade($siti, 'MAA61052', 70.0, 'A');  // A30 baru — Pengantar Ekonomi Makro (kode baru)
        $this->submitAndReview($siti, 'A30', 'pending');

        // 3) Hanya nilai kode kurikulum lama (rule c) → tidak bisa disetarakan → none.
        //    A40 lama: MAA62007+MAA61022 (berbeda dari baru: MAA62042+MAA61044).
        $budi = $this->createStudent('195020100003', 'Budi Santoso', 'S1 Ilmu Aktuaria');
        $this->giveGrade($budi, 'MAA62007', 85.0, 'A'); // A40 kode lama saja → set baru tidak match
        $this->giveGrade($budi, 'MAA61022', 85.0, 'A'); // rule c → decision=none, tombol disabled

        // 4) Belum lengkap matkul komponennya (cuma ambil 1 dari 2).
        $dewi = $this->createStudent('195020100004', 'Dewi Lestari', 'S1 Ilmu Aktuaria');
        $this->giveGrade($dewi, 'MAA62043', 90.0, 'A'); // A10 kode baru: MAA62043 saja, MAA61041 belum

        // 5) Tepat di batas 3.5 -> gagal Adendum PKS Lama (strictly greater than).
        $rudi = $this->createStudent('195020100005', 'Rudi Hartono', 'S1 Ilmu Aktuaria');
        $this->giveGrade($rudi, 'MAA62003', 70.0, 'B+');
        $this->giveGrade($rudi, 'MAA61007', 70.0, 'B+');

        // 6) Eligible PKS Baru tapi DITOLAK admin -> demo alur "ajukan ulang".
        //    NH=C → avg≤3.5 → eligibleLama=false; NA=95,96,97 ≥ threshold=91 → eligibleBaru=true.
        $maya = $this->createStudent('195020100006', 'Maya Putri', 'S1 Ilmu Aktuaria');
        $this->giveGrade($maya, 'MAA62045', 95.0, 'C', 'Genap 2425');
        $this->giveGrade($maya, 'MAA61016', 96.0, 'C', 'Genap 2425');
        $this->giveGrade($maya, 'MAA62047', 97.0, 'C', 'Genap 2425');
        $this->submitAndReview($maya, 'A50', 'rejected', $admin, 'No Induk tidak cocok dengan data akademik. Mohon ajukan ulang setelah verifikasi.');

        $this->command?->info('Demo seeder selesai: 1 admin + 6 mahasiswa (lihat README untuk kredensial).');
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'name' => 'Admin PAI',
            'email' => 'admin@pai.test',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
        ]);
    }

    private function createStudent(string $noInduk, string $nama, string $prodi): Student
    {
        $email = strtolower(explode(' ', $nama)[0]).'@pai.test';

        $user = User::factory()->create([
            'name' => $nama,
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => UserRole::Student,
        ]);

        return Student::create([
            'user_id' => $user->id,
            'no_induk' => $noInduk,
            'nama' => $nama,
            'prodi' => $prodi,
        ]);
    }

    private function giveGrade(Student $student, string $courseCode, float $na, string $nh, string $semester = 'Genap 2223'): void
    {
        $course = Course::where('code', $courseCode)->firstOrFail();

        CourseGrade::create([
            'course_id' => $course->id,
            'semester' => $semester,
            'no_induk' => $student->no_induk,
            'nama' => $student->nama,
            'na' => $na,
            'nh' => $nh,
            'grade_point' => GradeScale::toWeight($nh),
        ]);
    }

    /**
     * Pool nilai "mahasiswa lain" (no_induk generik, bukan demo student)
     * supaya course_thresholds dihitung dari data yang realistis, bukan
     * di-hardcode. Pool [60,65,70,75,80,85,90,95,100,55] x percentile 80
     * -> threshold_na = 91.0 (PERCENTILE.INC), dipakai skenario Ahmad & Budi.
     */
    private function seedThresholdPools(): void
    {
        $pool = [60, 65, 70, 75, 80, 85, 90, 95, 100, 55];
        $courseCodes = ['MAA62043', 'MAA61041', 'MAA62042', 'MAA61044', 'MAA62045', 'MAA61016', 'MAA62047'];

        foreach ($courseCodes as $code) {
            $course = Course::where('code', $code)->firstOrFail();

            foreach ($pool as $i => $na) {
                CourseGrade::create([
                    'course_id' => $course->id,
                    'semester' => 'Ganjil 2122',
                    'no_induk' => '999000'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                    'nama' => 'Mahasiswa Pool '.$i,
                    'na' => $na,
                    'nh' => $this->naToNh($na),
                    'grade_point' => GradeScale::toWeight($this->naToNh($na)),
                ]);
            }

            $this->thresholds->recomputeForCourse($course);
        }
    }

    private function naToNh(float $na): string
    {
        return match (true) {
            $na >= 85 => 'A',
            $na >= 80 => 'B+',
            $na >= 75 => 'B',
            $na >= 70 => 'C+',
            $na >= 65 => 'C',
            $na >= 60 => 'D+',
            $na >= 55 => 'D',
            default => 'E',
        };
    }

    /**
     * Evaluasi eligibility sungguhan (bukan hardcode) lalu buat Submission +
     * snapshot submission_courses dari decidingComponents() -- konsisten
     * dengan yang dilakukan SubmissionController di alur asli.
     */
    private function submitAndReview(Student $student, string $moduleCode, string $status, ?User $admin = null, ?string $rejectionReason = null): void
    {
        $module = PaiModule::where('code', $moduleCode)->firstOrFail();
        $result = $this->eligibility->evaluate($student, $module);

        if ($result->decision === 'none') {
            throw new \RuntimeException("DemoSeeder: {$student->nama} ternyata tidak eligible untuk {$moduleCode}, cek ulang data nilainya.");
        }

        $submission = Submission::create([
            'student_id' => $student->id,
            'pai_module_id' => $module->id,
            'scheme' => $result->decision,
            'price' => $result->price,
            'status' => $status,
            'rejection_reason' => $status === 'rejected' ? $rejectionReason : null,
            'reviewed_by' => $admin?->id,
            'reviewed_at' => $admin ? now() : null,
        ]);

        foreach ($result->decidingComponents() ?? [] as $component) {
            SubmissionCourse::create([
                'submission_id' => $submission->id,
                'course_id' => $component['course_id'],
                'na' => $component['na'],
                'nh' => $component['nh'],
                'grade_point' => $component['grade_point'],
            ]);
        }
    }
}
