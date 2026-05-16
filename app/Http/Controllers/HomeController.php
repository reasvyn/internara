<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Setup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
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
