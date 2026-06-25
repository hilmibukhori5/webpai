<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportCourseGradesRequest;
use App\Imports\CourseGradesImport;
use App\Models\Course;
use App\Models\CourseGrade;
use App\Models\GradeUploadStatus;
use App\Models\PaiModule;
use App\Models\Submission;
use App\Services\ThresholdService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class GradeImportController extends Controller
{
    public function create(): View
    {
        $this->authorize('viewAny', Submission::class);

        $years = $this->academicYears();

        $modules = PaiModule::orderBy('code')
            ->with('moduleCourses.course')
            ->get()
            ->map(function ($module) {
                // Kursus dikelompokkan per prodi agar A20 menampilkan Aktuaria & Matematika terpisah.
                $module->coursesByProdi = $module->moduleCourses
                    ->groupBy('prodi')
                    ->map(fn ($mcs) => $mcs->pluck('course')->unique('id')->sortBy('code')->values());

                $module->hasMultipleProdi = $module->coursesByProdi->count() > 1;

                return $module;
            });

        // [course_id => [period => count]]
        $uploadCounts = CourseGrade::selectRaw('course_id, semester, COUNT(*) as cnt')
            ->groupBy('course_id', 'semester')
            ->get()
            ->groupBy('course_id')
            ->map(fn ($rows) => $rows->keyBy('semester')->map(fn ($r) => $r->cnt));

        // [course_id => [period => GradeUploadStatus]]
        $skipStatuses = GradeUploadStatus::all()
            ->groupBy('course_id')
            ->map(fn ($rows) => $rows->keyBy('period'));

        $matrix = $modules->map(function ($module) use ($years, $uploadCounts, $skipStatuses) {
            $rows = collect();

            foreach ($module->coursesByProdi as $prodi => $courses) {
                foreach ($courses as $course) {
                    // 'Keduanya' → tampilkan dua baris (Genap + Ganjil) untuk course yang ditawarkan di kedua semester.
                    $semTypes = $course->semester_type === 'Keduanya'
                        ? ['Genap', 'Ganjil']
                        : [$course->semester_type];

                    foreach ($semTypes as $effectiveSemType) {
                        $cells = collect($years)->mapWithKeys(function ($year) use ($course, $effectiveSemType, $uploadCounts, $skipStatuses) {
                            $period = "{$effectiveSemType} {$year}";
                            $count = $uploadCounts->get($course->id, collect())->get($period, 0);
                            $skip = $skipStatuses->get($course->id, collect())->get($period);
                            $status = $count > 0 ? 'uploaded' : ($skip ? 'skipped' : 'empty');

                            return [$year => compact('status', 'count', 'skip')];
                        });

                        $rows->push([
                            'course' => $course,
                            'cells' => $cells,
                            'prodi' => $prodi,
                            'effective_semester_type' => $effectiveSemType,
                        ]);
                    }
                }
            }

            return ['module' => $module, 'rows' => $rows, 'hasMultipleProdi' => $module->hasMultipleProdi];
        });

        return view('admin.grades.import', compact('matrix', 'years'));
    }

    public function store(ImportCourseGradesRequest $request): RedirectResponse
    {
        $course = Course::findOrFail($request->validated('course_id'));
        $year = $request->validated('year');
        $semType = $request->validated('semester_override')
            ?? ($course->semester_type === 'Keduanya' ? 'Genap' : $course->semester_type);
        $period = "{$semType} {$year}";

        $import = new CourseGradesImport($course->id, $period);

        try {
            DB::transaction(function () use ($course, $period, $import, $request) {
                // Replace semantics: hapus data lama sebelum import baru
                CourseGrade::where('course_id', $course->id)->where('semester', $period)->delete();

                // Upload berarti tidak "dilewati" lagi
                GradeUploadStatus::where('course_id', $course->id)->where('period', $period)->delete();

                Excel::import($import, $request->file('file'));
            });
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Gagal membaca file. Pastikan formatnya sesuai contoh (xlsx/xls/csv) dan tidak corrupt.');
        }

        $imported = $import->importedCount();

        app(ThresholdService::class)->recomputeForCourse($course);

        if ($import->failures()->isNotEmpty()) {
            $messages = $import->failures()->map(
                fn ($f) => "Baris {$f->row()}: ".implode(', ', $f->errors())
            )->all();

            return back()
                ->with('status', "{$imported} baris berhasil diimport untuk {$course->code} — {$period}.")
                ->with('importErrors', $messages);
        }

        return back()->with('status', "{$imported} baris berhasil diimport untuk {$course->code} — {$period}.");
    }

    public function skip(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Submission::class);

        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'period' => ['required', 'string', 'max:20'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        GradeUploadStatus::updateOrCreate(
            ['course_id' => $validated['course_id'], 'period' => $validated['period']],
            ['note' => $validated['note'] ?? null],
        );

        return back()->with('status', "Periode {$validated['period']} ditandai dilewati.");
    }

    public function unskip(GradeUploadStatus $gradeUploadStatus): RedirectResponse
    {
        $this->authorize('viewAny', Submission::class);

        $gradeUploadStatus->delete();

        return back()->with('status', 'Status dilewati dihapus.');
    }

    public function distribution(Course $course, string $year): View
    {
        $this->authorize('viewAny', Submission::class);

        if (! preg_match('/^\d{4}$/', $year)) {
            abort(400);
        }

        $semType = request('sem');
        if (! in_array($semType, ['Genap', 'Ganjil'], true)) {
            $semType = $course->semester_type === 'Keduanya' ? 'Genap' : $course->semester_type;
        }
        $semester = "{$semType} {$year}";
        $grades = CourseGrade::where('course_id', $course->id)
            ->where('semester', $semester)
            ->orderByDesc('na')
            ->orderBy('nama')
            ->get();

        $threshold = $course->threshold()->first();
        $yearLabel = substr($year, 0, 2).'/'.substr($year, 2, 2);

        return view('admin.grades.distribution', compact('course', 'year', 'yearLabel', 'semester', 'grades', 'threshold'));
    }

    public function zeroNaForYear(string $year): RedirectResponse
    {
        $this->authorize('viewAny', Submission::class);

        if (! preg_match('/^\d{4}$/', $year)) {
            abort(400);
        }

        $affected = CourseGrade::where('semester', 'like', "% {$year}")
            ->update(['na' => 0]);

        $label = substr($year, 0, 2).'/'.substr($year, 2, 2);

        return back()->with('status', "NA di-nol-kan untuk {$affected} baris nilai TA {$label}.");
    }

    /** Tahun akademik yang ditampilkan di matrix, dari 2021/22 sampai tahun sekarang+1. */
    private function academicYears(): array
    {
        $currentYear = now()->month >= 8 ? now()->year : now()->year - 1;
        $years = [];

        for ($y = 2021; $y <= $currentYear + 1; $y++) {
            $years[] = substr((string) $y, 2).substr((string) ($y + 1), 2);
        }

        return $years;
    }
}
