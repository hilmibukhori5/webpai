<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    /**
     * Lihat daftar mahasiswa/submission (dashboard admin).
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Lihat detail 1 submission.
     */
    public function view(User $user, Submission $submission): bool
    {
        return $user->isAdmin();
    }

    /**
     * Setujui/tolak submission (docs/spec.md bagian 6 & 8 Fase 5).
     */
    public function review(User $user, Submission $submission): bool
    {
        return $user->isAdmin();
    }

    /**
     * Upload bukti bayar & formulir terisi (di luar 8 fase asli, ditambah
     * belakangan). Cuma mahasiswa pemilik submission itu sendiri, dan cuma
     * kalau submission-nya sudah disetujui admin.
     */
    public function manageDocuments(User $user, Submission $submission): bool
    {
        return $user->isStudent()
            && $user->student?->id === $submission->student_id
            && $submission->status === 'approved';
    }
}
