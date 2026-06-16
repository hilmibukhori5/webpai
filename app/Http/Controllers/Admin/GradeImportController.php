<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportCourseGradesRequest;
use App\Imports\CourseGradesImport;
use App\Models\Course;
use App\Services\ThresholdService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class GradeImportController extends Controller
{
    /**
     * Tampilkan form import nilai.
     */
    public function create(): View
    {
        $courses = Course::orderBy('code')->get();

        return view('admin.grades.import', ['courses' => $courses]);
    }

    /**
     * Proses upload file, simpan ke course_grades, lalu recompute
     * course_thresholds untuk course terkait.
     */
    public function store(ImportCourseGradesRequest $request): RedirectResponse
    {
        $course = Course::findOrFail($request->validated('course_id'));

        $import = new CourseGradesImport($course->id, $request->validated('semester'));

        try {
            Excel::import($import, $request->file('file'));
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Gagal membaca file. Pastikan formatnya sesuai contoh (xlsx/xls/csv) dan tidak corrupt.');
        }

        $imported = $import->importedCount();

        app(ThresholdService::class)->recomputeForCourse($course);

        if ($import->failures()->isNotEmpty()) {
            $messages = $import->failures()->map(
                fn ($failure) => "Baris {$failure->row()}: ".implode(', ', $failure->errors())
            )->all();

            return back()
                ->with('status', "{$imported} baris berhasil diimport untuk {$course->code} - {$course->name}.")
                ->with('importErrors', $messages);
        }

        return back()->with('status', "{$imported} baris berhasil diimport untuk {$course->code} - {$course->name}.");
    }
}
