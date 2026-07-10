<?php

declare(strict_types=1);

use App\Assignment\Notifications\AssignmentNotification;
use App\Core\Channels\CustomDatabaseChannel;

test('notification constructs with correct data', function () {
    $notification = new AssignmentNotification(
        internshipName: 'PT Maju',
        assignmentTitle: 'Laporan',
        dueDate: '2026-01-01',
    );

    expect($notification->internshipName)->toBe('PT Maju');
    expect($notification->assignmentTitle)->toBe('Laporan');
    expect($notification->dueDate)->toBe('2026-01-01');
});

test('notification via channels', function () {
    $notification = new AssignmentNotification('Test', 'Test');

    expect($notification->via(new stdClass))->toBe(['mail', 'broadcast', CustomDatabaseChannel::class]);
});
