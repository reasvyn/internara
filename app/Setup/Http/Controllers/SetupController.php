<?php

declare(strict_types=1);

namespace App\Setup\Http\Controllers;

use App\Core\Http\Controllers\BaseController;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;

final class SetupController extends BaseController
{
    public function redirect(): RedirectResponse
    {
        return redirect()->route('setup');
    }

    public function cleanup(): Response
    {
        Session::forget(['setup.form_data', 'setup.authorized']);

        return response()->noContent();
    }
}
