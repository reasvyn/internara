<?php

declare(strict_types=1);

namespace App\User\Http\Controllers;

use App\Core\Http\Controllers\BaseController;
use App\User\Services\DashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends BaseController
{
    public function __invoke(Request $request, DashboardService $dashboardService): RedirectResponse
    {
        $user = $request->user();
        $dashboard = $dashboardService->getDashboardForUser($user);

        return redirect()->route($dashboard);
    }
}
