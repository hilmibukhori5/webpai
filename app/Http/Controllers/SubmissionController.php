<?php

namespace App\Http\Controllers;

use App\Models\PaiModule;
use App\Models\Submission;
use App\Models\SubmissionCourse;
use App\Services\EligibilityService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubmissionController extends Controller
{
    public function __construct(private EligibilityService $eligibility) {}

    /**
     * Tampilkan form persetujuan (bersedia diajukan + bersedia bayar)
     * sebelum submit pengajuan penyetaraan modul.
     */
    public function create(PaiModule $paiModule): View|RedirectResponse
    {
        $student = auth()->user()->student;

        if (! $student) {
            return redirect()->route('dashboard')->with('error', 'Profil mahasiswa belum lengkap.');
        }

        if (! $paiModule->moduleCourses()->where('prodi', $student->prodi)->exists()) {
            return redirect()->route('dashboard')->with('error', 'Modul ini tidak tersedia untuk program studi kamu.');
        }

        $result = $this->eligibility->evaluate($student, $paiModule);

        if ($result->decision === 'none') {
            return redirect()->route('dashboard')->with('error', 'Belum eligible untuk mengajukan modul ini.');
        }

        $existing = Submission::where('student_id', $student->id)
            ->where('pai_module_id', $paiModule->id)
            ->first();

        if ($existing && in_array($existing->status, ['pending', 'approved'], true)) {
            return redirect()->route('dashboard')->with('error', 'Modul ini sudah diajukan/disetujui sebelumnya.');
        }

        return view('student.submissions.create', [
            'module' => $paiModule,
            'result' => $result,
        ]);
    }

    /**
     * Submit pengajuan: buat/update submissions (status pending) + snapshot
     * submission_courses dari matkul komponen yang menentukan keputusan.
     */
    public function store(Request $request, PaiModule $paiModule): RedirectResponse
    {
        $request->validate([
            'bersedia_diajukan' => ['accepted'],
            'bersedia_bayar' => ['accepted'],
        ]);

        $student = auth()->user()->student;

        if (! $student) {
            return redirect()->route('dashboard')->with('error', 'Profil mahasiswa belum lengkap.');
        }

        if (! $paiModule->moduleCourses()->where('prodi', $student->prodi)->exists()) {
            return redirect()->route('dashboard')->with('error', 'Modul ini tidak tersedia untuk program studi kamu.');
        }

        // Re-evaluasi di server, jangan percaya state dari form/client.
        $result = $this->eligibility->evaluate($student, $paiModule);

        if ($result->decision === 'none') {
            return redirect()->route('dashboard')->with('error', 'Belum eligible untuk mengajukan modul ini.');
        }

        $existing = Submission::where('student_id', $student->id)
            ->where('pai_module_id', $paiModule->id)
            ->first();

        if ($existing && in_array($existing->status, ['pending', 'approved'], true)) {
            return redirect()->route('dashboard')->with('error', 'Modul ini sudah diajukan/disetujui sebelumnya.');
        }

        DB::transaction(function () use ($student, $paiModule, $result) {
            $submission = Submission::updateOrCreate(
                ['student_id' => $student->id, 'pai_module_id' => $paiModule->id],
                [
                    'scheme' => $result->decision,
                    'price' => $result->price,
                    'status' => 'pending',
                    'rejection_reason' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                ],
            );

            // Bersihkan snapshot lama kalau ini pengajuan ulang (setelah ditolak).
            $submission->submissionCourses()->delete();

            foreach ($result->decidingComponents() ?? [] as $component) {
                SubmissionCourse::create([
                    'submission_id' => $submission->id,
                    'course_id' => $component['course_id'],
                    'na' => $component['na'],
                    'nh' => $component['nh'],
                    'grade_point' => $component['grade_point'],
                ]);
            }
        });

        return redirect()->route('dashboard')->with('status', "Pengajuan modul {$paiModule->code} - {$paiModule->name} berhasil dikirim, menunggu review admin.");
    }
}
