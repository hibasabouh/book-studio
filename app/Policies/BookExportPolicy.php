<?php

namespace App\Policies;

use App\Models\BookExport;
use App\Models\User;

class BookExportPolicy
{
    /**
     * Admins bypass all checks.
     */
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, BookExport $export): bool
    {
        return $user->id === $export->user_id;
    }
}
