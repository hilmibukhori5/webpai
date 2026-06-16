<?php

use App\Http\Controllers\Admin\GradeImportController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\SubmissionReviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\SubmissionController;
use App\Mail\ApprovedModule;
use App\Mail\RejectedModule;
use App\Models\PaiModule;
use App\Models\Submission;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome', ['modules' => PaiModule::orderBy('code')->get()]);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'role:student'])->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');

    Route::get('/modules/{paiModule:code}/ajukan', [SubmissionController::class, 'create'])->name('submissions.create');
    Route::post('/modules/{paiModule:code}/ajukan', [SubmissionController::class, 'store'])->name('submissions.store');
});

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/grades/import', [GradeImportController::class, 'create'])->name('grades.import.create');
    Route::post('/grades/import', [GradeImportController::class, 'store'])->name('grades.import.store');

    Route::get('/students', [AdminStudentController::class, 'index'])->name('students.index');
    Route::get('/students/{student}', [AdminStudentController::class, 'show'])->name('students.show');

    Route::post('/submissions/{submission}/approve', [SubmissionReviewController::class, 'approve'])->name('submissions.approve');
    Route::post('/submissions/{submission}/reject', [SubmissionReviewController::class, 'reject'])->name('submissions.reject');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/{scheme}', [ReportController::class, 'export'])->name('reports.export')->whereIn('scheme', ['lama', 'baru']);
});

// Preview email tanpa kirim sungguhan (gantinya MailHog/Mailpit, karena
// MAIL_MAILER sekarang SMTP asli ke Gmail). Cuma aktif di local.
if (app()->environment('local')) {
    Route::get('/dev/mail-preview/approved', function () {
        $submission = Submission::with(['student', 'paiModule'])->first();
        abort_if(! $submission, 404, 'Belum ada submission buat preview. Buat dulu lewat seeder/dashboard.');

        return new ApprovedModule($submission);
    });

    Route::get('/dev/mail-preview/rejected', function () {
        $submission = Submission::with(['student', 'paiModule'])->whereNotNull('rejection_reason')->first()
            ?? Submission::with(['student', 'paiModule'])->first();
        abort_if(! $submission, 404, 'Belum ada submission buat preview. Buat dulu lewat seeder/dashboard.');

        if (! $submission->rejection_reason) {
            $submission->rejection_reason = 'Contoh alasan penolakan (preview).';
        }

        return new RejectedModule($submission);
    });
}

require __DIR__.'/auth.php';
