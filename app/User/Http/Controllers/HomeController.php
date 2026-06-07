<?php

declare(strict_types=1);

namespace App\User\Http\Controllers;

use App\Core\Http\Controllers\BaseController;
use App\Setup\Entities\SetupEntity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeController extends BaseController
{
    public function __invoke(Request $request): RedirectResponse
    {
        if (! SetupEntity::get()->isInstalled()) {
            return redirect()->route('setup');
        }

        if ($request->user() === null) {
            return redirect()->route('login');
        }

        return redirect()->route('dashboard');
    }
}
