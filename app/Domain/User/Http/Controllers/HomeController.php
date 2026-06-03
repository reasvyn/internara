<?php

declare(strict_types=1);

namespace App\Domain\User\Http\Controllers;

use App\Domain\Admin\Aggregates\Setup\Models\Setup;
use App\Domain\Core\Http\Controllers\BaseController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeController extends BaseController
{
    public function __invoke(Request $request): RedirectResponse
    {
        if (! Setup::state()->isInstalled()) {
            return redirect()->route('setup');
        }

        if ($request->user() === null) {
            return redirect()->route('login');
        }

        return redirect()->route('dashboard');
    }
}
