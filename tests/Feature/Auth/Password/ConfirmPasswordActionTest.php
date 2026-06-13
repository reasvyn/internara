<?php

declare(strict_types=1);

use App\Auth\Password\Actions\ConfirmPasswordAction;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'password' => Hash::make('current-password'),
    ]);

    $this->action = app(ConfirmPasswordAction::class);
});

test('confirms password when it matches', function () {
    $this->action->execute($this->user, 'current-password');

    expect(Session::get('auth.password_confirmed_at'))->toBeInt();
});

test('throws exception when password does not match', function () {
    expect(fn () => $this->action->execute($this->user, 'wrong-password'))
        ->toThrow(RuntimeException::class, __('auth.password_confirmation_failed'));
});

test('does not set session on failed confirmation', function () {
    try {
        $this->action->execute($this->user, 'wrong-password');
    } catch (RuntimeException) {
    }

    expect(Session::get('auth.password_confirmed_at'))->toBeNull();
});

test('session timestamp is recent after confirmation', function () {
    $this->action->execute($this->user, 'current-password');

    $confirmedAt = Session::get('auth.password_confirmed_at');
    expect($confirmedAt)->toBeGreaterThanOrEqual(now()->subMinute()->timestamp);
    expect($confirmedAt)->toBeLessThanOrEqual(now()->timestamp);
});

test('multiple confirmations update session each time', function () {
    $this->action->execute($this->user, 'current-password');
    $firstTime = Session::get('auth.password_confirmed_at');

    sleep(1);
    $this->action->execute($this->user, 'current-password');
    $secondTime = Session::get('auth.password_confirmed_at');

    expect($secondTime)->toBeGreaterThan($firstTime);
});
