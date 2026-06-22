<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Submission;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Submission::class);

        $search = trim((string) $request->input('search', ''));

        $students = Student::withCount([
            'submissions as approved_count' => fn ($q) => $q->where('status', 'approved'),
            'submissions as pending_count' => fn ($q) => $q->where('status', 'pending'),
            'submissions as rejected_count' => fn ($q) => $q->where('status', 'rejected'),
        ])
            ->when($search, fn ($q) => $q->where(
                fn ($q2) => $q2->where('nama', 'like', "%{$search}%")
                               ->orWhere('no_induk', 'like', "%{$search}%")
            ))
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        return view('admin.students.index', [
            'students' => $students,
            'search' => $search,
            'metrics' => [
                'totalStudents' => Student::count(),
                'totalPending' => Submission::where('status', 'pending')->count(),
                'totalApproved' => Submission::where('status', 'approved')->count(),
            ],
        ]);
    }

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
