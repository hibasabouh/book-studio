<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\Chapter;
use App\Models\User;

class ChapterPolicy
{
    /**
     * Admins bypass all checks.
     */
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, Chapter $chapter): bool
    {
        return $user->id === $chapter->book->user_id;
    }

    /**
     * Creating a chapter is authorized against the parent book:
     * $this->authorize('create', [Chapter::class, $book]).
     */
    public function create(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }

    public function update(User $user, Chapter $chapter): bool
    {
        return $user->id === $chapter->book->user_id;
    }

    public function delete(User $user, Chapter $chapter): bool
    {
        return $user->id === $chapter->book->user_id;
    }
}
