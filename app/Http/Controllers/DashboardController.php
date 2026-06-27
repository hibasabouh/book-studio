<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /**
     * Render the user dashboard shell. Content is hydrated client-side from the
     * auth store; book stats will be added once books exist.
     */
    public function index(): View
    {
        return view('dashboard.index');
    }
}
