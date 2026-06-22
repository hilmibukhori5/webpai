<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectSubmissionRequest;
use App\Models\Submission;
use Illuminate\Http\RedirectResponse;

class SubmissionReviewController extends Controller
{
    public function approve(Submission $submission): RedirectResponse
    {
        $this->authorize('review', $submission);

        if ($submission->status !== 'pending') {
            return back()->with('error', 'Submission ini sudah diproses sebelumnya.');
        }

        $submission->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('status', "Modul {$submission->paiModule->code} - {$submission->paiModule->name} telah disetujui.");
    }

    public function reject(RejectSubmissionRequest $request, Submission $submission): RedirectResponse
    {
        $this->authorize('review', $submission);

        if ($submission->status !== 'pending') {
            return back()->with('error', 'Submission ini sudah diproses sebelumnya.');
        }

        $submission->update([
            'status' => 'rejected',
            'rejection_reason' => $request->validated('rejection_reason'),
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('status', "Modul {$submission->paiModule->code} - {$submission->paiModule->name} ditolak.");
    }
}
