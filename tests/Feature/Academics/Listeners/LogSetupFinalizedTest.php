<?php

declare(strict_types=1);

namespace Tests\Feature\Academics\Listeners;

use App\Academics\Events\SetupFinalized;
use App\Academics\Listeners\LogSetupFinalized;
use Illuminate\Support\Facades\Log;

test('log setup finalized listener logs setup event via smart logger', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('setup_finalized', \Mockery::on(function (array $context): bool {
            return isset($context['payload']['department_id'])
                && isset($context['payload']['installed_at'])
                && ($context['module'] ?? null) === 'SysAdmin';
        }));

    $listener = new LogSetupFinalized;
    $event = new SetupFinalized(
        departmentId: 'uuid-dept-123',
        installedAt: new \DateTimeImmutable('2026-06-05 12:00:00'),
    );

    $listener->handle($event);
});
