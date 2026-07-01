<?php

declare(strict_types=1);

use App\Enrollment\AccountApplication\Events\AccountApplicationApproved;
use App\Enrollment\AccountApplication\Events\AccountApplicationRejected;
use App\Enrollment\AccountApplication\Models\AccountApplication;

test('account application approved has application payload', function () {
    $app = new class extends AccountApplication {};
    $app->forceFill(['id' => 'a-1']);

    $event = new AccountApplicationApproved($app);

    expect($event->application->id)->toBe('a-1');
    expect($event->eventName())->toBe('account_application.approved');
});

test('account application rejected has application payload', function () {
    $app = new class extends AccountApplication {};
    $app->forceFill(['id' => 'a-2']);

    $event = new AccountApplicationRejected($app);

    expect($event->application->id)->toBe('a-2');
    expect($event->eventName())->toBe('account_application.rejected');
});
