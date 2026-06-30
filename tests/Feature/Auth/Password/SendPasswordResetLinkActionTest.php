<?php

declare(strict_types=1);

use App\Auth\Password\Actions\SendPasswordResetLinkAction;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Password;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->action = app(SendPasswordResetLinkAction::class);
});

test('sends password reset link for valid email', function () {
    $status = $this->action->execute($this->user->email);

    expect($status)->toBe(Password::RESET_LINK_SENT);
});

test('returns error for non-existent email', function () {
    $status = $this->action->execute('nonexistent@test.com');

    expect($status)->toBe(Password::INVALID_USER);
});
