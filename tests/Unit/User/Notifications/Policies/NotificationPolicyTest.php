<?php

declare(strict_types=1);

use App\User\Models\User;
use App\User\Notifications\Models\Notification;
use App\User\Notifications\Policies\NotificationPolicy;

beforeEach(function () {
    $this->policy = new NotificationPolicy;
});

test('before allows super admin for any ability', function () {
    $user = mock(User::class);
    $user->shouldReceive('hasRole')->with('super_admin')->andReturn(true);

    $result = $this->policy->before($user, 'view');

    expect($result->allowed())->toBeTrue();
});

test('before returns null for non-super-admin', function () {
    $user = mock(User::class);
    $user->shouldReceive('hasRole')->with('super_admin')->andReturn(false);

    $result = $this->policy->before($user, 'view');

    expect($result)->toBeNull();
});

test('viewAny allows everyone', function () {
    $user = User::factory()->make();

    expect($this->policy->viewAny($user))->toBeTrue();
});

test('view allows notification owner', function () {
    $user = User::factory()->make(['id' => 'user-1']);
    $notification = Notification::factory()->make(['user_id' => 'user-1']);

    expect($this->policy->view($user, $notification))->toBeTrue();
});

test('view denies non-owner', function () {
    $user = User::factory()->make(['id' => 'user-1']);
    $notification = Notification::factory()->make(['user_id' => 'user-2']);

    expect($this->policy->view($user, $notification))->toBeFalse();
});

test('create allows admin users', function () {
    $user = mock(User::class);
    $user->shouldReceive('hasAnyRole')->with(['super_admin', 'admin'])->andReturn(true);

    expect($this->policy->create($user))->toBeTrue();
});

test('create denies non-admin users', function () {
    $user = mock(User::class);
    $user->shouldReceive('hasAnyRole')->with(['super_admin', 'admin'])->andReturn(false);

    expect($this->policy->create($user))->toBeFalse();
});

test('update allows notification owner', function () {
    $user = User::factory()->make(['id' => 'user-1']);
    $notification = Notification::factory()->make(['user_id' => 'user-1']);

    expect($this->policy->update($user, $notification))->toBeTrue();
});

test('update denies non-owner', function () {
    $user = User::factory()->make(['id' => 'user-1']);
    $notification = Notification::factory()->make(['user_id' => 'user-2']);

    expect($this->policy->update($user, $notification))->toBeFalse();
});

test('delete allows admin users', function () {
    $user = mock(User::class);
    $user->shouldReceive('hasAnyRole')->with(['super_admin', 'admin'])->andReturn(true);

    expect($this->policy->delete($user, Notification::factory()->make()))->toBeTrue();
});

test('delete denies non-admin users', function () {
    $user = mock(User::class);
    $user->shouldReceive('hasAnyRole')->with(['super_admin', 'admin'])->andReturn(false);

    expect($this->policy->delete($user, Notification::factory()->make()))->toBeFalse();
});
