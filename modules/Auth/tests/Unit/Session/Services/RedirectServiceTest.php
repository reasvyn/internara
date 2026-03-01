<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Unit\Session\Services;


use Modules\Auth\Services\RedirectService;
use Modules\Permission\Enums\Role;
use Modules\Setting\Facades\Setting;
use Modules\User\Models\User;

beforeEach(function () {
    Setting::shouldReceive('getValue')->andReturn(true);
});

test('it redirects unverified users to verification notice', function () {
    $user = mock(User::class);
    $user->shouldReceive('hasAnyRole')->andReturn(false);
    $user->shouldReceive('hasVerifiedEmail')->andReturn(false);

    $service = new RedirectService;
    $url = $service->getTargetUrl($user);

    expect($url)->toBe(route('verification.notice'));
});

test('it redirects admins to admin dashboard', function () {
    $user = mock(User::class);
    $user->shouldReceive('hasVerifiedEmail')->andReturn(true);
    $user
        ->shouldReceive('hasAnyRole')
        ->with([Role::SUPER_ADMIN->value, Role::ADMIN->value])
        ->andReturn(true);

    $service = new RedirectService;
    $url = $service->getTargetUrl($user);

    expect($url)->toBe(route('admin.dashboard'));
});

test('it redirects students to student dashboard', function () {
    $user = mock(User::class);
    $user->shouldReceive('hasAnyRole')->andReturn(false);
    $user->shouldReceive('hasVerifiedEmail')->andReturn(true);
    $user->shouldReceive('hasRole')->with(Role::TEACHER->value)->andReturn(false);
    $user->shouldReceive('hasRole')->with(Role::MENTOR->value)->andReturn(false);
    $user->shouldReceive('hasRole')->with(Role::STUDENT->value)->andReturn(true);

    $service = new RedirectService;
    $url = $service->getTargetUrl($user);

    expect($url)->toBe(route('student.dashboard'));
});
