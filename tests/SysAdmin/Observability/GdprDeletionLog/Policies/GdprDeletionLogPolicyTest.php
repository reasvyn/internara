<?php

declare(strict_types=1);

use App\SysAdmin\Observability\GdprDeletionLog\Models\GdprDeletionLog;
use App\SysAdmin\Observability\GdprDeletionLog\Policies\GdprDeletionLogPolicy;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->policy = new GdprDeletionLogPolicy;
});

test('view any is allowed for admin users', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    expect($this->policy->viewAny($user))->toBeTrue();
});

test('view any is denied for non admin users', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    expect($this->policy->viewAny($user))->toBeFalse();
});

test('view is allowed for admin users', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $log = GdprDeletionLog::factory()->make();

    expect($this->policy->view($user, $log))->toBeTrue();
});

test('view is denied for non admin users', function () {
    $user = User::factory()->create();
    $user->assignRole('student');
    $log = GdprDeletionLog::factory()->make();

    expect($this->policy->view($user, $log))->toBeFalse();
});

test('create is allowed for admin users', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    expect($this->policy->create($user))->toBeTrue();
});

test('create is denied for non admin users', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    expect($this->policy->create($user))->toBeFalse();
});
