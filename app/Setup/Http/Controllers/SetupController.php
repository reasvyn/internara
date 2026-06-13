<?php

declare(strict_types=1);

namespace App\Setup\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;

final class SetupController
{
    public function redirect(): RedirectResponse
    {
        return redirect()->route('setup');
    }

    public function cleanup(): JsonResponse
    {
        Session::forget(['setup.form_data', 'setup.authorized']);

        return response()->noContent();
    }
}
