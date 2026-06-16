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
}
