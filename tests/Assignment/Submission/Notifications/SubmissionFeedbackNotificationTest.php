<?php

declare(strict_types=1);

use App\Assignment\Submission\Notifications\SubmissionFeedbackNotification;
use App\Core\Channels\CustomDatabaseChannel;

test('notification constructs with feedback data', function () {
    $notification = new SubmissionFeedbackNotification(
        assignmentTitle: 'Laporan',
        status: 'graded',
        feedback: 'Good job!',
    );

    expect($notification->assignmentTitle)->toBe('Laporan');
    expect($notification->feedback)->toBe('Good job!');
});

test('notification via channels', function () {
    $notification = new SubmissionFeedbackNotification('Test', 'graded');

    expect($notification->via(new stdClass))->toBe(['mail', 'broadcast', CustomDatabaseChannel::class]);
});
