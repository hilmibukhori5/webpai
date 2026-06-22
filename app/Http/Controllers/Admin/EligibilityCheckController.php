<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseGrade;
use App\Models\ManualSubmission;
use App\Models\PaiModule;
use App\Models\Student;
use App\Models\Submission;
use App\Services\EligibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class EligibilityCheckController extends Controller
{
    public function __construct(private EligibilityService $eligibility) {}

    public function index(): RedirectResponse
    {
        $this->authorize('viewAny', Submission::class);

        return redirect()->route('admin.eligibility.show', 'A10');
    }

    public function show(PaiModule $paiModule): View
    {
        $this->authorize('viewAny', Submission::class);

        $modules = PaiModule::orderBy('code')->get();
        $rows = $this->computeRows($paiModule);

        $eligible    = $rows->filter(fn ($r) => $r['result']->decision !== 'none')->values();
        $notEligible = $rows->filter(fn ($r) => $r['result']->decision === 'none')->values();
        $manualTotal = ManualSubmission::count();

        return view('admin.eligibility.index', compact(
            'modules', 'paiModule', 'eligible', 'notEligible', 'manualTotal'
        ));
    }

    public function export(PaiModule $paiModule): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorize('viewAny', Submission::class);

        $rows = $this->computeRows($paiModule);

        $toExport = $rows->filter(fn ($r) =>
            $r['result']->decision !== 'none' &&
            in_array($r['submission_status'], ['registered', 'unregistered'], true)
        )->values();

        $filename = 'eligible-belum-diajukan-'.$paiModule->code.'.xlsx';

        return Excel::download(
            new \App\Exports\EligibleBelumDiajukanExport($toExport, $paiModule),
            $filename
        );
    }

    public function exportAll(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorize('viewAny', Submission::class);

        $modules   = PaiModule::orderBy('code')->get();
        $byStudent = [];

        foreach ($modules as $module) {
            $rows = $this->computeRows($module);

            foreach ($rows as $row) {
                if ($row['result']->decision === 'none') {
                    continue;
                }
                if (! in_array($row['submission_status'], ['registered', 'unregistered'], true)) {
                    continue;
                }

                $nim = $row['student']->no_induk;
                if (! isset($byStudent[$nim])) {
                    $byStudent[$nim] = ['student' => $row['student'], 'modules' => []];
                }
                $byStudent[$nim]['modules'][] = $module;
            }
        }

        // Sort by student name
        uasort($byStudent, fn ($a, $b) => strcmp($a['student']->nama ?? '', $b['student']->nama ?? ''));

        return Excel::download(
            new \App\Exports\EligibleBelumDiajukanAllExport(collect(array_values($byStudent))),
            'eligible-belum-diajukan-semua-modul.xlsx'
        );
    }

    // -------------------------------------------------------------------------

    private function computeRows(PaiModule $paiModule): Collection
    {
        $coursesPerProdi = $paiModule->moduleCourses()
            ->get()
            ->groupBy('prodi')
            ->map(fn ($mcs) => $mcs->pluck('course_id')->unique()->values());

        $allCourseIds = $coursesPerProdi->flatten()->unique()->values();

        $registeredByNim = Student::with('user')
            ->whereIn('prodi', $coursesPerProdi->keys())
            ->get()
            ->keyBy('no_induk');

        $nimsWithGrades = CourseGrade::whereIn('course_id', $allCourseIds)
            ->selectRaw('no_induk, MAX(nama) as display_nama')
            ->groupBy('no_induk')
            ->get()
            ->keyBy('no_induk');

        $manualNims = ManualSubmission::where('pai_module_id', $paiModule->id)
            ->pluck('no_induk')->flip()->toArray();

        $webNims = Submission::where('pai_module_id', $paiModule->id)
            ->join('students', 'students.id', '=', 'submissions.student_id')
            ->pluck('students.no_induk')->flip()->toArray();

        $unregisteredNims = $nimsWithGrades->keys()->diff($registeredByNim->keys());

        $nimsByProdi = [];
        foreach ($coursesPerProdi as $prodi => $courseIds) {
            $nimsByProdi[$prodi] = CourseGrade::whereIn('course_id', $courseIds)
                ->whereIn('no_induk', $unregisteredNims->values())
                ->distinct()->pluck('no_induk')->flip()->toArray();
        }

        $rows = collect();

        foreach ($registeredByNim as $noInduk => $student) {
            $rows->push([
                'student'           => $student,
                'result'            => $this->eligibility->evaluate($student, $paiModule),
                'registered'        => true,
                'inferred_prodi'    => null,
                'submission_status' => $this->submissionStatus($noInduk, $webNims, $manualNims, true),
            ]);
        }

        foreach ($unregisteredNims as $noInduk) {
            $gradeInfo = $nimsWithGrades->get($noInduk);

            foreach ($nimsByProdi as $prodi => $nimSet) {
                if (! array_key_exists($noInduk, $nimSet)) {
                    continue;
                }

                $pseudo = new Student([
                    'no_induk' => $noInduk,
                    'nama'     => $gradeInfo->display_nama,
                    'prodi'    => $prodi,
                ]);

                $rows->push([
                    'student'           => $pseudo,
                    'result'            => $this->eligibility->evaluate($pseudo, $paiModule),
                    'registered'        => false,
                    'inferred_prodi'    => $prodi,
                    'submission_status' => $this->submissionStatus($noInduk, $webNims, $manualNims, false),
                ]);
            }
        }

        return $rows->sortBy([
            [fn ($r) => $r['result']->decision === 'none' ? 1 : 0, 'asc'],
            [fn ($r) => $r['student']->nama, 'asc'],
        ])->values();
    }

    private function submissionStatus(string $noInduk, array $webNims, array $manualNims, bool $registered): string
    {
        if (array_key_exists($noInduk, $webNims)) {
            return 'web';
        }
        if (array_key_exists($noInduk, $manualNims)) {
            return 'manual';
        }

        return $registered ? 'registered' : 'unregistered';
    }
}
