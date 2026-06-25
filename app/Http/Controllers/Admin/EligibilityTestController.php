<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseGrade;
use App\Models\CourseThreshold;
use App\Models\PaiModule;
use App\Models\Student;
use App\Models\Submission;
use App\Models\User;
use App\Services\EligibilityResult;
use App\Services\EligibilityService;
use App\Support\GradeScale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EligibilityTestController extends Controller
{
    public function __construct(private EligibilityService $eligibility) {}

    public function create(): View
    {
        $this->authorize('viewAny', Submission::class);

        [$modules, $allCourses, $moduleCourseMap] = $this->viewData();

        return view('admin.eligibility-test', compact('modules', 'allCourses', 'moduleCourseMap'));
    }

    public function test(Request $request): View
    {
        $this->authorize('viewAny', Submission::class);

        $validated = $request->validate([
            'module_code' => ['required', 'exists:pai_modules,code'],
            'prodi'       => ['required', 'in:S1 Ilmu Aktuaria,S1 Matematika'],
            'grades'      => ['required', 'array', 'min:1'],
            'grades.*.course_code'   => ['required', 'exists:courses,code'],
            'grades.*.na'            => ['required', 'numeric', 'min:0', 'max:100'],
            'grades.*.nh'            => ['required', 'in:A,B+,B,C+,C,D+,D,E'],
            'grades.*.semester'      => ['required', 'string', 'max:20'],
            'grades.*.threshold_na'  => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $result = null;

        try {
            DB::beginTransaction();

            $user = User::create([
                'name'     => 'Eligibility Test',
                'email'    => '__eligtest_'.Str::uuid().'@test.invalid',
                'password' => Hash::make('x'),
                'role'     => UserRole::Student,
            ]);

            $student = Student::create([
                'user_id'  => $user->id,
                'no_induk' => '__TEST'.Str::uuid(),
                'nama'     => 'Test',
                'prodi'    => $validated['prodi'],
            ]);

            foreach ($validated['grades'] as $row) {
                $course = Course::where('code', $row['course_code'])->firstOrFail();

                CourseGrade::create([
                    'course_id'   => $course->id,
                    'semester'    => $row['semester'],
                    'no_induk'    => $student->no_induk,
                    'nama'        => 'Test',
                    'na'          => (float) $row['na'],
                    'nh'          => $row['nh'],
                    'grade_point' => GradeScale::toWeight($row['nh']),
                ]);

                // Override threshold untuk course ini jika diisi — di-rollback setelah evaluasi.
                $tna = $row['threshold_na'] ?? null;
                if ($tna !== null && $tna !== '') {
                    CourseThreshold::updateOrCreate(
                        ['course_id' => $course->id],
                        ['percentile' => 80, 'threshold_na' => (float) $tna, 'computed_at' => now()],
                    );
                }
            }

            $module = PaiModule::where('code', $validated['module_code'])->firstOrFail();
            $result = $this->eligibility->evaluate($student, $module);

        } finally {
            DB::rollBack();
        }

        [$modules, $allCourses, $moduleCourseMap] = $this->viewData();

        return view('admin.eligibility-test', compact('modules', 'allCourses', 'moduleCourseMap', 'result'))
            ->with('input', $validated);
    }

    private function viewData(): array
    {
        $modules = PaiModule::orderBy('code')->get();

        $allCourses = Course::orderBy('code')
            ->get()
            ->map(fn ($c) => ['code' => $c->code, 'name' => $c->name, 'sks' => $c->sks])
            ->all();

        $moduleCourseMap = PaiModule::with('moduleCourses.course')
            ->orderBy('code')
            ->get()
            ->mapWithKeys(function ($module) {
                $byProdi = $module->moduleCourses
                    ->groupBy('prodi')
                    ->map(fn ($mcs) => $mcs->groupBy('curriculum')
                        ->map(fn ($items) => $items->map(fn ($mc) => [
                            'code' => $mc->course->code,
                            'name' => $mc->course->name,
                            'sks'  => $mc->sks,
                        ])->values()->all()
                    )->all()
                )->all();

                return [$module->code => $byProdi];
            })->all();

        return [$modules, $allCourses, $moduleCourseMap];
    }
}
