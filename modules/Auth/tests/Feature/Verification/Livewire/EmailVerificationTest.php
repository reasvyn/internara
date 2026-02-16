<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature\Verification\Livewire;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Services\Contracts\AuthService;
use Modules\Permission\Models\Role;
use Modules\User\Models\User;


describe('Email Verification', function () {
    beforeEach(function () {
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::create(['name' => 'student', 'guard_name' => 'web']);
    });

    test('a user can verify their email address using the service logic', function () {
        $user = User::factory()->create(['email_verified_at' => null]);
        $user->assignRole('student');

        $authService = app(AuthService::class);

        $result = $authService->verifyEmail(
            (string) $user->id,
            sha1($user->getEmailForVerification()),
        );

        expect($result)
            ->toBeTrue()
            ->and($user->fresh()->hasVerifiedEmail())
            ->toBeTrue();
    });

    test('unverified users are redirected to verification notice [SYRS-NF-501]', function () {
        $user = User::factory()->create(['email_verified_at' => null]);
        $user->assignRole('student');

        $this->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertRedirect(route('verification.notice'));
    });
});
