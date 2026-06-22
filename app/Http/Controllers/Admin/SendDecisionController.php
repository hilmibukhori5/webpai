<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BulkDecisionMail;
use App\Models\Student;
use App\Models\Submission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class SendDecisionController extends Controller
{
    public function store(Student $student): RedirectResponse
    {
        $this->authorize('viewAny', Submission::class);

        $hasDecided = $student->submissions()
            ->whereIn('status', ['approved', 'rejected'])
            ->exists();

        if (! $hasDecided) {
            return back()->with('error', 'Belum ada keputusan yang bisa dikirim — semua pengajuan masih pending.');
        }

        Mail::to($student->user->email)->send(new BulkDecisionMail($student));

        $student->update(['decision_sent_at' => now()]);

        return back()->with('status', "Keputusan telah dikirim ke {$student->user->email}.");
    }
}
