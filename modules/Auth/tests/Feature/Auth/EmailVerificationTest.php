<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Modules\Auth\Services\Contracts\AuthService;
use Modules\Permission\Models\Role;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
    Role::create(['name' => 'student', 'guard_name' => 'web']);
});

test('a user can verify their email address', function () {
    $user = User::factory()->create(['email_verified_at' => null]);
    $user->assignRole('student');

    $verificationUrl = URL::signedRoute('verification.verify', [
        'id' => $user->id,
        'hash' => sha1($user->getEmailForVerification()),
    ]);

    $authService = app(AuthService::class);

    // Test the service logic (the component test is handled separately)
    $result = $authService->verifyEmail((string) $user->id, sha1($user->getEmailForVerification()));

    expect($result)->toBeTrue();
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('unverified users are redirected from dashboard to verification notice', function () {
    $user = User::factory()->create(['email_verified_at' => null]);
    $user->assignRole('student');

    $this->actingAs($user)->get('/student')->assertRedirect('/auth/email/verify');
});
