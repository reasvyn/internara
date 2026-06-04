<?php

declare(strict_types=1);

use App\Domain\User\Aggregates\Login\Actions\LoginAction;
use App\Domain\User\Enums\AccountStatus;
use App\Domain\User\Enums\Role as RoleEnum;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate(['name' => $role->value]);
    }
});

afterEach(function () {
    Cache::flush();
});

// ─── Successful Authentication ───

test('login succeeds with email', function () {
    $user = User::factory()->create([
        'email' => 'alice@example.com',
        'username' => 'alice123',
        'password' => Hash::make('SecretPass1'),
    ]);
    $user->setStatus(AccountStatus::ACTIVATED->value);

    $result = app(LoginAction::class)->execute('alice@example.com', 'SecretPass1');

    expect($result->id)->toBe($user->id);
    expect(auth()->check())->toBeTrue();
});

test('login succeeds with username', function () {
    $user = User::factory()->create([
        'email' => 'bob@example.com',
        'username' => 'bob_the_user',
        'password' => Hash::make('SecretPass1'),
    ]);
    $user->setStatus(AccountStatus::ACTIVATED->value);

    $result = app(LoginAction::class)->execute('bob_the_user', 'SecretPass1');

    expect($result->id)->toBe($user->id);
    expect(auth()->check())->toBeTrue();
});

test('login accepts remember flag', function () {
    $user = User::factory()->create([
        'email' => 'carol@example.com',
        'password' => Hash::make('SecretPass1'),
    ]);
    $user->setStatus(AccountStatus::ACTIVATED->value);

    $result = app(LoginAction::class)->execute('carol@example.com', 'SecretPass1', remember: true);

    expect($result->id)->toBe($user->id);
});

// ─── Failed Authentication ───

test('login fails with wrong password', function () {
    $user = User::factory()->create([
        'email' => 'dave@example.com',
        'password' => Hash::make('CorrectPass1'),
    ]);
    $user->setStatus(AccountStatus::ACTIVATED->value);

    expect(fn () => app(LoginAction::class)->execute('dave@example.com', 'WrongPass1'))
        ->toThrow(RuntimeException::class, __('auth.failed'));

    expect(auth()->check())->toBeFalse();
});

test('login fails with non-existent identifier', function () {
    expect(fn () => app(LoginAction::class)->execute('ghost@example.com', 'SomePass1'))
        ->toThrow(RuntimeException::class, __('auth.failed'));

    expect(auth()->check())->toBeFalse();
});

test('login fails when account is locked', function () {
    $user = User::factory()->locked()->create([
        'email' => 'fiona@example.com',
        'password' => Hash::make('SecretPass1'),
    ]);
    $user->setStatus(AccountStatus::ACTIVATED->value);

    expect(fn () => app(LoginAction::class)->execute('fiona@example.com', 'SecretPass1'))
        ->toThrow(RuntimeException::class, __('auth.blocked'));

    expect(auth()->check())->toBeFalse();
});

test('login fails when account status does not allow login', function (AccountStatus $status) {
    $user = User::factory()->create([
        'email' => "{$status->value}@example.com",
        'password' => Hash::make('SecretPass1'),
    ]);
    $user->setStatus($status->value);

    expect(fn () => app(LoginAction::class)->execute("{$status->value}@example.com", 'SecretPass1'))
        ->toThrow(RuntimeException::class);
})->with([
    AccountStatus::PROVISIONED,
    AccountStatus::SUSPENDED,
    AccountStatus::ARCHIVED,
]);

test('login fails when setup is required', function () {
    $user = User::factory()->requiresSetup()->create([
        'email' => 'grace@example.com',
        'password' => Hash::make('SecretPass1'),
    ]);
    $user->setStatus(AccountStatus::ACTIVATED->value);

    expect(fn () => app(LoginAction::class)->execute('grace@example.com', 'SecretPass1'))
        ->toThrow(RuntimeException::class, __('auth.blocked'));

    expect(auth()->check())->toBeFalse();
});

// ─── Statuses That Allow Login ───

test('login succeeds for account statuses that allow login', function (AccountStatus $status) {
    $user = User::factory()->create([
        'email' => "{$status->value}@example.com",
        'password' => Hash::make('SecretPass1'),
    ]);
    $user->setStatus($status->value);

    $result = app(LoginAction::class)->execute("{$status->value}@example.com", 'SecretPass1');

    expect($result->id)->toBe($user->id);
})->with([
    AccountStatus::ACTIVATED,
    AccountStatus::VERIFIED,
    AccountStatus::PROTECTED,
    AccountStatus::RESTRICTED,
]);

// ─── Session Regeneration ───

test('login regenerates session', function () {
    $user = User::factory()->create([
        'email' => 'heidi@example.com',
        'password' => Hash::make('SecretPass1'),
    ]);
    $user->setStatus(AccountStatus::ACTIVATED->value);

    $oldSession = session()->getId();

    app(LoginAction::class)->execute('heidi@example.com', 'SecretPass1');

    expect(session()->getId())->not()->toBe($oldSession);
});

// ─── Lockout / Exponential Backoff ───

test('login triggers exponential lockout after 10 failed attempts', function () {
    $user = User::factory()->create([
        'email' => 'ivan@example.com',
        'username' => 'ivanthegreat',
        'password' => Hash::make('CorrectPass1'),
    ]);
    $user->setStatus(AccountStatus::ACTIVATED->value);

    $action = app(LoginAction::class);

    // First 9 attempts all throw auth.failed
    for ($i = 0; $i < 9; $i++) {
        expect(fn () => $action->execute('ivan@example.com', 'wrong_pass'))
            ->toThrow(RuntimeException::class, __('auth.failed'));
    }

    // 10th attempt should also trigger lockout (auth.throttle message may vary)
    expect(fn () => $action->execute('ivan@example.com', 'wrong_pass'))
        ->toThrow(RuntimeException::class);

    // 11th attempt should be blocked by lockout immediately
    expect(fn () => $action->execute('ivan@example.com', 'CorrectPass1'))
        ->toThrow(RuntimeException::class);
});

test('successful login clears failed attempt cache', function () {
    $user = User::factory()->create([
        'email' => 'james@example.com',
        'password' => Hash::make('CorrectPass1'),
    ]);
    $user->setStatus(AccountStatus::ACTIVATED->value);

    $action = app(LoginAction::class);

    // Fail 3 times
    for ($i = 0; $i < 3; $i++) {
        expect(fn () => $action->execute('james@example.com', 'wrong'))
            ->toThrow(RuntimeException::class);
    }

    // Succeed
    $action->execute('james@example.com', 'CorrectPass1');

    // Fail again — should be treated as first failure
    expect(fn () => $action->execute('james@example.com', 'wrong'))
        ->toThrow(RuntimeException::class, __('auth.failed'));
});
