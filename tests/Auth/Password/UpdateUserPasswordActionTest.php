<?php

declare(strict_types=1);

use App\Auth\Password\Actions\UpdateUserPasswordAction;
use App\Auth\Password\Events\PasswordUpdated;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);

    $this->action = app(UpdateUserPasswordAction::class);
});

test('updates password with valid new password', function () {
    Event::fake([PasswordUpdated::class]);

    $this->action->execute($this->user, 'NewPass123');

    $this->user->refresh();
    expect(Hash::check('NewPass123', $this->user->password))->toBeTrue();
});

test('dispatches password updated event', function () {
    Event::fake([PasswordUpdated::class]);

    $this->action->execute($this->user, 'NewPass123');

    Event::assertDispatched(PasswordUpdated::class, function (PasswordUpdated $event) {
        return $event->user->id === $this->user->id;
    });
});

test('rejects password that is too short', function () {
    expect(fn () => $this->action->execute($this->user, 'Short1A'))
        ->toThrow(ValidationException::class);
});

test('rejects password without uppercase', function () {
    expect(fn () => $this->action->execute($this->user, 'lowercase1'))
        ->toThrow(ValidationException::class);
});

test('rejects password without numbers', function () {
    expect(fn () => $this->action->execute($this->user, 'NoNumbers'))
        ->toThrow(ValidationException::class);
});

test('rejects empty password', function () {
    expect(fn () => $this->action->execute($this->user, ''))
        ->toThrow(ValidationException::class);
});

test('does not update password on validation failure', function () {
    $oldHash = $this->user->password;

    try {
        $this->action->execute($this->user, 'short');
    } catch (ValidationException) {
    }

    $this->user->refresh();
    expect($this->user->password)->toBe($oldHash);
});

test('does not dispatch event on validation failure', function () {
    Event::fake([PasswordUpdated::class]);

    try {
        $this->action->execute($this->user, 'short');
    } catch (ValidationException) {
    }

    Event::assertNotDispatched(PasswordUpdated::class);
});
