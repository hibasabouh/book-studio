<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    /**
     * Render the admin dashboard shell (admin enforced client-side + by the
     * `can:admin` gate on the data endpoint).
     */
    public function index(): View
    {
        return view('admin.index');
    }

    /**
     * Admin overview stats. Book/chapter counts will be added with those models.
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'users_total' => User::count(),
            'users_verified' => User::whereNotNull('email_verified_at')->count(),
            'recent_users' => UserResource::collection(
                User::latest()->take(5)->get()
            ),
        ]);
    }
}
