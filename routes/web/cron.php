<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Laravel\Pulse\Pulse;

Route::get('/cron/{secret}', function (string $secret) {
    if ($secret !== config('app.cron_secret')) {
        abort(403, 'Invalid cron secret.');
    }

    $output = [];

    $exitCode = Artisan::call('schedule:run');
    $output['schedule:run'] = $exitCode;

    if (class_exists(Pulse::class)) {
        $exitCode = Artisan::call('pulse:check');
        $output['pulse:check'] = $exitCode;
    }

    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'commands' => $output,
    ]);
})->name('cron');
