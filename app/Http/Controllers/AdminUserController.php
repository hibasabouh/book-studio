<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Http\Requests\IndexUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\BookResource;
use App\Http\Resources\UserResource;
use App\Models\Chapter;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
{
    /**
     * Render the user management shell.
     */
    public function index(IndexUserRequest $request): JsonResponse|View
    {
        // Web page is a thin shell; it fetches /api/admin/users itself.
        if (! $request->expectsJson()) {
            return view('admin.users');
        }

        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 15);

        $users = User::query()
            ->when($validated['search'] ?? null, fn ($q, $search) => $q->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")
            ))
            ->when($validated['role'] ?? null, fn ($q, $role) => $q->where('role', $role))
            ->when($validated['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate($perPage);

        return UserResource::collection($users)->response();
    }

    /**
     * Render the user details shell. Stats and books are hydrated client-side from
     * the JSON endpoint below.
     */
    public function show(User $user): View
    {
        return view('admin.user-details', ['userId' => $user->id]);
    }

    /**
     * User details payload: the user record, content stats, and their books.
     */
    public function details(User $user): JsonResponse
    {
        $booksTotal = $user->books()->count();
        $chaptersTotal = Chapter::whereHas('book', fn ($q) => $q->where('user_id', $user->id))->count();

        return response()->json([
            'user' => new UserResource($user),
            'stats' => [
                'books_total' => $booksTotal,
                'chapters_total' => $chaptersTotal,
            ],
            'books' => BookResource::collection(
                $user->books()->withCount('chapters')->latest()->get()
            ),
        ]);
    }

    /**
     * Update a user's account. Every field is optional; only those supplied are
     * applied. Admins cannot change their own role (so they can't accidentally
     * lock themselves out of the admin area).
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        if (array_key_exists('role', $validated)) {
            if ($user->is($request->user())) {
                return response()->json(['message' => __('messages.cannot_change_own_role')], 422);
            }

            // Set directly rather than mass-assign — `role` is intentionally not fillable.
            $user->role = Role::from($validated['role']);
        }

        if (array_key_exists('status', $validated)) {
            // Set directly rather than mass-assign — `status` is intentionally not fillable.
            $user->status = UserStatus::from($validated['status']);
        }

        if (array_key_exists('name', $validated)) {
            $user->name = $validated['name'];
        }

        if (array_key_exists('email', $validated) && $user->email !== $validated['email']) {
            // A new address must be re-verified by its owner.
            $user->email = $validated['email'];
            $user->email_verified_at = null;
        }

        if (! empty($validated['password'])) {
            $user->password = $validated['password']; // hashed via the model cast
        }

        if ($request->hasFile('avatar')) {
            // Remove the previous file (skip external Google URLs, which aren't on disk).
            if ($user->avatar && ! str_starts_with($user->avatar, 'http')) {
                delete_file_if_exist($user->avatar);
            }

            $user->avatar = upload_file($request->file('avatar'), 'avatar', 'avatars');
        }

        $user->save();

        return (new UserResource($user))->response();
    }

    /**
     * Delete a user. Admins cannot delete their own account here.
     */
    public function destroy(User $user): JsonResponse
    {
        if ($user->is(auth()->user())) {
            return response()->json(['message' => __('messages.cannot_delete_self')], 422);
        }

        $user->delete();

        return response()->json(['message' => __('messages.user_deleted')]);
    }
}
