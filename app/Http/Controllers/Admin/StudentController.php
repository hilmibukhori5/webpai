<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Submission;
use Illuminate\Contracts\View\View;

class StudentController extends Controller
{
    /**
     * Dashboard admin: list PER MAHASISWA dengan rekap approved/pending/rejected
     * (docs/spec.md bagian 6 & 8 Fase 5).
     */
    public function index(): View
    {
        $this->authorize('viewAny', Submission::class);

        $students = Student::withCount([
            'submissions as approved_count' => fn ($query) => $query->where('status', 'approved'),
            'submissions as pending_count' => fn ($query) => $query->where('status', 'pending'),
            'submissions as rejected_count' => fn ($query) => $query->where('status', 'rejected'),
        ])->orderBy('nama')->paginate(15);

        return view('admin.students.index', [
            'students' => $students,
            'metrics' => [
                'totalStudents' => Student::count(),
                'totalPending' => Submission::where('status', 'pending')->count(),
                'totalApproved' => Submission::where('status', 'approved')->count(),
            ],
        ]);
    }

    /**
     * Detail: daftar modul yang diajukan mahasiswa ini + rincian nilai
     * komponen (submission_courses) + skema + harga + tombol Setujui/Tolak.
     */
    public function show(Student $student): View
    {
        $this->authorize('viewAny', Submission::class);

        $submissions = $student->submissions()
            ->with(['paiModule', 'submissionCourses.course', 'reviewedBy'])
            ->latest()
            ->get();

        return view('admin.students.show', [
            'student' => $student,
            'submissions' => $submissions,
        ]);
    }
}
