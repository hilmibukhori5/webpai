<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectSubmissionRequest;
use App\Models\Submission;
use Illuminate\Http\RedirectResponse;

class SubmissionReviewController extends Controller
{
    /**
     * Setujui submission. Cuma submission yang masih "pending" yang bisa
     * diproses (cegah double-action tak sengaja).
     */
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

        // TODO Fase 6: trigger Mailable ApprovedModule.

        return back()->with('status', "Modul {$submission->paiModule->code} - {$submission->paiModule->name} telah disetujui.");
    }

    /**
     * Tolak submission, alasan wajib diisi.
     */
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

        // TODO Fase 6: trigger Mailable RejectedModule.

        return back()->with('status', "Modul {$submission->paiModule->code} - {$submission->paiModule->name} ditolak.");
    }
}
