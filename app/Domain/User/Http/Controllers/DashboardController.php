<?php

declare(strict_types=1);

namespace App\Domain\User\Http\Controllers;

use App\Domain\Core\Http\Controllers\BaseController;
use App\Domain\User\Services\DashboardService;
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
