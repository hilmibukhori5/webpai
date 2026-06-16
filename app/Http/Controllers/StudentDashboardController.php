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
                'metrics' => ['eligible' => 0, 'diajukan' => 0, 'disetujui' => 0],
            ]);
        }

        $existingSubmissions = Submission::where('student_id', $student->id)
            ->get()
            ->keyBy('pai_module_id');

        $cards = PaiModule::orderBy('code')->get()->map(function (PaiModule $module) use ($student, $existingSubmissions) {
            return [
                'module' => $module,
                'color' => 'bg-module-'.strtolower($module->code),
                'componentNames' => $module->coursesForCurriculum('baru')->pluck('name')->implode(', '),
                'result' => $this->eligibility->evaluate($student, $module),
                'submission' => $existingSubmissions->get($module->id),
            ];
        });

        return view('dashboard', [
            'student' => $student,
            'cards' => $cards,
            'metrics' => [
                'eligible' => $cards->filter(fn (array $card) => $card['result']->decision !== 'none')->count(),
                'diajukan' => $existingSubmissions->count(),
                'disetujui' => $existingSubmissions->filter(fn (Submission $s) => $s->status === 'approved')->count(),
            ],
        ]);
    }
}
