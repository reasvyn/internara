<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Dashboard\Services\DashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Redirect the user to their appropriate dashboard based on their role.
     */
    public function __invoke(Request $request, DashboardService $dashboardService): RedirectResponse
    {
        $user = $request->user();
        $dashboard = $dashboardService->getDashboardForUser($user);

        return redirect()->route($dashboard);
    }
}
