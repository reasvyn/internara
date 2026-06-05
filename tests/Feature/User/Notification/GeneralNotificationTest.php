<?php

declare(strict_types=1);

use App\User\Enums\Role as RoleEnum;
use App\User\Models\User;
use App\User\Notification\Models\Notification as DbNotification;
use App\User\Notification\Notifications\GeneralNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate(['name' => $role->value]);
    }
});

test('general notification can send email and database notifications', function () {
    Notification::fake();

    $user = User::factory()->create();
    $user->assignRole('student');

    $notif = new GeneralNotification(
        type: 'student',
        title: 'System Alert',
        message: 'Notification Message Details',
        link: '/some-path',
        sendEmail: true
    );

    $user->notify($notif);

    Notification::assertSentTo(
        $user,
        GeneralNotification::class,
        function ($notification) {
            return $notification->title === 'System Alert'
                && $notification->message === 'Notification Message Details';
        }
    );
});

test('general notification records in database correctly', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    $notif = new GeneralNotification(
        type: 'student',
        title: 'DB Save Alert',
        message: 'Saved to Database',
        link: '/some-path',
        sendEmail: false
    );

    $user->notify($notif);

    $dbNotification = DbNotification::where('user_id', $user->id)
        ->where('title', 'DB Save Alert')
        ->first();

    expect($dbNotification)->not->toBeNull();
    expect($dbNotification->message)->toBe('Saved to Database');
    expect($dbNotification->type)->toBe('student');
});
