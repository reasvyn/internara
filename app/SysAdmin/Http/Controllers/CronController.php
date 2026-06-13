<?php

declare(strict_types=1);

namespace App\SysAdmin\Http\Controllers;

use App\Core\Exceptions\UnauthorizedException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Laravel\Pulse\Pulse;

final class CronController
{
    public function __invoke(string $secret): JsonResponse
    {
        if ($secret !== config('app.cron_secret')) {
            throw new UnauthorizedException('Invalid cron secret.');
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
    }
}
