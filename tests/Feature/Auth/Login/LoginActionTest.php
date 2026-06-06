<?php

declare(strict_types=1);

use App\Auth\Login\Actions\LoginAction;
use App\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'student', 'guard_name' => 'web']);
    $this->user = User::factory()->create([
        'password' => Hash::make('correct-password'),
        'status' => 'activated',
    ]);
    $this->action = app(LoginAction::class);
});

test('login succeeds with valid email and password', function () {
    $result = $this->action->execute($this->user->email, 'correct-password');

    expect($result->id)->toBe($this->user->id);
    expect(auth()->check())->toBeTrue();
});

test('login succeeds with valid username and password', function () {
    $result = $this->action->execute($this->user->username, 'correct-password');

    expect($result->id)->toBe($this->user->id);
});

test('login fails with wrong password', function () {
    expect(fn () => $this->action->execute($this->user->email, 'wrong-password'))
        ->toThrow(RuntimeException::class, trans('auth.failed'));
});

test('login fails with non-existent email', function () {
    expect(fn () => $this->action->execute('nonexistent@test.com', 'password'))
        ->toThrow(RuntimeException::class, trans('auth.failed'));
});

test('login fails when account is locked', function () {
    $this->user->update(['locked_at' => now(), 'locked_reason' => 'too_many_failed_attempts']);

    expect(fn () => $this->action->execute($this->user->email, 'correct-password'))
        ->toThrow(RuntimeException::class, trans('auth.blocked'));
});

test('login fails when account status does not allow login', function () {
    $this->user->update(['status' => 'suspended']);

    expect(fn () => $this->action->execute($this->user->email, 'correct-password'))
        ->toThrow(RuntimeException::class, trans('auth.blocked'));
});

test('login fails when setup is required', function () {
    $this->user->update(['setup_required' => true]);

    expect(fn () => $this->action->execute($this->user->email, 'correct-password'))
        ->toThrow(RuntimeException::class, trans('auth.blocked'));
});

test('login locks account after 10 failed attempts', function () {
    for ($i = 0; $i < 10; $i++) {
        try {
            $this->action->execute($this->user->email, 'wrong');
        } catch (RuntimeException) {
        }
    }

    expect(fn () => $this->action->execute($this->user->email, 'correct-password'))
        ->toThrow(RuntimeException::class);
});

test('login clears failed attempts on success', function () {
    for ($i = 0; $i < 5; $i++) {
        try {
            $this->action->execute($this->user->email, 'wrong');
        } catch (RuntimeException) {
        }
    }

    $this->action->execute($this->user->email, 'correct-password');

    $attemptsKey = 'login:attempts:'.md5($this->user->email);
    expect(Cache::get($attemptsKey))->toBeNull();
});

test('login with remember flag', function () {
    $result = $this->action->execute($this->user->email, 'correct-password', remember: true);

    expect($result->id)->toBe($this->user->id);
});
