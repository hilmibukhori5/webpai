<?php

use App\Http\Controllers\Admin\EligibilityCheckController;
use App\Http\Controllers\Admin\EligibilityTestController;
use App\Http\Controllers\Admin\GradeImportController;
use App\Http\Controllers\Admin\ManualSubmissionController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SendDecisionController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\StudentLetterController;
use App\Http\Controllers\Admin\SubmissionReviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentDocumentController;
use App\Http\Controllers\SubmissionController;
use App\Mail\BulkDecisionMail;
use App\Models\PaiModule;
use App\Models\Student;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome', ['modules' => PaiModule::orderBy('code')->get()]);
})->name('welcome');

Route::get('/panduan', function () {
    return view('manual');
})->name('manual');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'role:student'])->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');

    Route::get('/modules/{paiModule:code}/ajukan', [SubmissionController::class, 'create'])->name('submissions.create');
    Route::post('/modules/{paiModule:code}/ajukan', [SubmissionController::class, 'store'])->name('submissions.store');

    Route::get('/dokumen', [StudentDocumentController::class, 'edit'])->name('student.documents.edit');
    Route::post('/dokumen', [StudentDocumentController::class, 'update'])->name('student.documents.update');
});

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/grades/import', [GradeImportController::class, 'create'])->name('grades.import.create');
    Route::post('/grades/import', [GradeImportController::class, 'store'])->name('grades.import.store');
    Route::post('/grades/skip', [GradeImportController::class, 'skip'])->name('grades.import.skip');
    Route::delete('/grades/skip/{gradeUploadStatus}', [GradeImportController::class, 'unskip'])->name('grades.import.unskip');
    Route::post('/grades/year/{year}/zero-na', [GradeImportController::class, 'zeroNaForYear'])->name('grades.zero-na-year');
    Route::get('/grades/courses/{course}/distribution/{year}', [GradeImportController::class, 'distribution'])->name('grades.distribution');

    Route::get('/students', [AdminStudentController::class, 'index'])->name('students.index');
    Route::get('/students/{student}', [AdminStudentController::class, 'show'])->name('students.show');
    Route::get('/students/{student}/surat-keterangan', [StudentLetterController::class, 'download'])->name('students.letter');
    Route::post('/students/{student}/send-decision', [SendDecisionController::class, 'store'])->name('students.send-decision');

    Route::post('/submissions/{submission}/approve', [SubmissionReviewController::class, 'approve'])->name('submissions.approve');
    Route::post('/submissions/{submission}/reject', [SubmissionReviewController::class, 'reject'])->name('submissions.reject');

    Route::get('/eligibility-test', [EligibilityTestController::class, 'create'])->name('eligibility-test.create');
    Route::post('/eligibility-test', [EligibilityTestController::class, 'test'])->name('eligibility-test.run');

    Route::get('/eligibility', [EligibilityCheckController::class, 'index'])->name('eligibility.index');
    Route::get('/eligibility/export-all', [EligibilityCheckController::class, 'exportAll'])->name('eligibility.export-all');
    Route::get('/eligibility/{paiModule:code}', [EligibilityCheckController::class, 'show'])->name('eligibility.show');
    Route::get('/eligibility/{paiModule:code}/export', [EligibilityCheckController::class, 'export'])->name('eligibility.export');

    Route::get('/manual-submissions', [ManualSubmissionController::class, 'create'])->name('manual-submissions.create');
    Route::post('/manual-submissions', [ManualSubmissionController::class, 'store'])->name('manual-submissions.store');
    Route::delete('/manual-submissions', [ManualSubmissionController::class, 'destroy'])->name('manual-submissions.destroy');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
});

// Preview email tanpa kirim sungguhan (gantinya MailHog/Mailpit, karena
// MAIL_MAILER sekarang SMTP asli ke Gmail). Cuma aktif di local.
if (app()->environment('local')) {
    Route::get('/dev/mail-preview/bulk-decision', function () {
        $student = Student::with(['submissions.paiModule', 'user'])->whereHas('submissions')->first();
        abort_if(! $student, 404, 'Belum ada mahasiswa dengan submission buat preview. Buat dulu lewat seeder/dashboard.');

        return new BulkDecisionMail($student);
    });
}

require __DIR__.'/auth.php';
