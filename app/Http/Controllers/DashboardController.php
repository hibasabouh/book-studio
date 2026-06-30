<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookResource;
use App\Models\Chapter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Render the user dashboard shell. Stats are hydrated client-side from the
     * stats endpoint below.
     */
    public function index(): View
    {
        return view('dashboard.index');
    }

    /**
     * Overview stats for the authenticated user: book/chapter totals plus the
     * most recently created books.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        $booksTotal = $user->books()->count();
        $chaptersTotal = Chapter::whereHas('book', fn ($q) => $q->where('user_id', $user->id))->count();

        return response()->json([
            'books_total' => $booksTotal,
            'chapters_total' => $chaptersTotal,
            'chapters_avg' => $booksTotal > 0 ? round($chaptersTotal / $booksTotal, 1) : 0,
            'recent_books' => BookResource::collection(
                $user->books()->withCount('chapters')->latest()->take(5)->get()
            ),
        ]);
    }
}
