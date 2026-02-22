<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Services\Contracts\RedirectService;

Route::get('/', function (RedirectService $redirectService) {
    if (auth()->check()) {
        return redirect($redirectService->getTargetUrl(auth()->user()));
    }

    return redirect()->route('login');
});
