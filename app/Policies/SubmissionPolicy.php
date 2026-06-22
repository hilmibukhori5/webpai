<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Submission $submission): bool
    {
        return $user->isAdmin();
    }

    public function review(User $user, Submission $submission): bool
    {
        return $user->isAdmin();
    }
}
