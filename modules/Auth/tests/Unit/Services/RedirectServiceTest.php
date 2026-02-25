<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Unit\Services;

use Illuminate\Support\Facades\Route;
use Modules\Auth\Services\RedirectService;
use Modules\Permission\Enums\Role;
use Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Redirect Service', function () {
    beforeEach(function () {
        $this->service = new RedirectService();
        
        // Mock standard routes to avoid RouteNotFoundException during testing
        Route::get('/login', fn() => 'login')->name('login');
        Route::get('/verify-email', fn() => 'verify')->name('verification.notice');
        Route::get('/admin/dashboard', fn() => 'admin')->name('admin.dashboard');
        Route::get('/teacher/dashboard', fn() => 'teacher')->name('teacher.dashboard');
        Route::get('/mentor/dashboard', fn() => 'mentor')->name('mentor.dashboard');
        Route::get('/student/dashboard', fn() => 'student')->name('student.dashboard');
    });

    test('it redirects unverified users to verification notice [SYRS-NF-505]', function () {
        $user = mock(User::class)->makePartial();
        $user->shouldReceive('hasVerifiedEmail')->andReturn(false);
        
        // We bypass the setting() call impact by focusing on the logic
        // Since setting() might fail without tables, we ensure we test the result
        expect($this->service->getTargetUrl($user))->toBe(route('verification.notice'));
    });

    test('it redirects administrators to admin dashboard [SYRS-NF-502]', function () {
        $user = mock(User::class)->makePartial();
        $user->shouldReceive('hasVerifiedEmail')->andReturn(true);
        $user->shouldReceive('hasAnyRole')->with([Role::SUPER_ADMIN->value, Role::ADMIN->value])->andReturn(true);
        
        expect($this->service->getTargetUrl($user))->toBe(route('admin.dashboard'));
    });

    test('it redirects teachers to teacher dashboard', function () {
        $user = mock(User::class)->makePartial();
        $user->shouldReceive('hasVerifiedEmail')->andReturn(true);
        $user->shouldReceive('hasAnyRole')->andReturn(false);
        $user->shouldReceive('hasRole')->with(Role::TEACHER->value)->andReturn(true);
        
        expect($this->service->getTargetUrl($user))->toBe(route('teacher.dashboard'));
    });

    test('it redirects students to student dashboard by default', function () {
        $user = mock(User::class)->makePartial();
        $user->shouldReceive('hasVerifiedEmail')->andReturn(true);
        $user->shouldReceive('hasAnyRole')->andReturn(false);
        $user->shouldReceive('hasRole')->andReturn(false);
        
        expect($this->service->getTargetUrl($user))->toBe(route('student.dashboard'));
    });
});
