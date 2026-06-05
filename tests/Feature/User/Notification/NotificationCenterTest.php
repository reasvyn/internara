<?php

declare(strict_types=1);

use App\Domain\User\Aggregates\Notification\Livewire\NotificationCenter;
use App\Domain\User\Aggregates\Notification\Models\Notification;
use App\Domain\User\Enums\Role as RoleEnum;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate(['name' => $role->value]);
    }
});

test('notification center query restricts notifications by role', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    // Create notifications for this student with different types
    Notification::create([
        'user_id' => $student->id,
        'type' => 'student',
        'title' => 'Student Alert',
        'message' => 'Detail message',
        'is_read' => false,
    ]);

    Notification::create([
        'user_id' => $student->id,
        'type' => 'everyone',
        'title' => 'Broadcast Alert',
        'message' => 'Broadcast detail',
        'is_read' => false,
    ]);

    Notification::create([
        'user_id' => $student->id,
        'type' => 'teacher',
        'title' => 'Teacher Alert',
        'message' => 'Teacher detail',
        'is_read' => false,
    ]);

    $test = Livewire::actingAs($student)
        ->test(NotificationCenter::class);

    $rows = $test->instance()->rows();
    expect($rows->items())->toHaveCount(2);
});

test('user can mark notification as read', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    $notification = Notification::create([
        'user_id' => $user->id,
        'type' => 'everyone',
        'title' => 'Important Alert',
        'message' => 'Details',
        'is_read' => false,
    ]);

    Livewire::actingAs($user)
        ->test(NotificationCenter::class)
        ->call('markAsRead', $notification->id);

    expect($notification->fresh()->is_read)->toBeTrue();
});

test('user can mark selected notifications as read', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    $n1 = Notification::create([
        'user_id' => $user->id,
        'type' => 'everyone',
        'title' => 'Alert 1',
        'is_read' => false,
    ]);

    $n2 = Notification::create([
        'user_id' => $user->id,
        'type' => 'everyone',
        'title' => 'Alert 2',
        'is_read' => false,
    ]);

    Livewire::actingAs($user)
        ->test(NotificationCenter::class)
        ->set('selectedIds', [$n1->id, $n2->id])
        ->call('markSelectedAsRead');

    expect($n1->fresh()->is_read)->toBeTrue();
    expect($n2->fresh()->is_read)->toBeTrue();
});
