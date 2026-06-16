<?php

namespace App\Http\Controllers;

use App\Models\PaiModule;
use App\Models\Submission;
use App\Services\EligibilityService;
use Illuminate\Contracts\View\View;

class StudentDashboardController extends Controller
{
    public function __construct(private EligibilityService $eligibility) {}

    public function index(): View
    {
        $student = auth()->user()->student;

        if (! $student) {
            return view('dashboard', [
                'student' => null,
                'cards' => collect(),
            ]);
        }

        $existingSubmissions = Submission::where('student_id', $student->id)
            ->get()
            ->keyBy('pai_module_id');

        $cards = PaiModule::orderBy('code')->get()->map(function (PaiModule $module) use ($student, $existingSubmissions) {
            return [
                'module' => $module,
                'result' => $this->eligibility->evaluate($student, $module),
                'submission' => $existingSubmissions->get($module->id),
            ];
        });

        return view('dashboard', [
            'student' => $student,
            'cards' => $cards,
        ]);
    }
}
