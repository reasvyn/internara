<?php

declare(strict_types=1);

use App\Actions\User\LockUserAccountAction;
use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    User::macro('isLocked', function () {
        return $this->locked_at !== null;
    });
});

describe('execute', function () {
    it('locks a user account', function () {
        $user = UserFactory::new()->create();

        app(LockUserAccountAction::class)->execute($user, 'too_many_failed_attempts');

        expect($user->fresh()->locked_at)->not->toBeNull();
    });

    it('does nothing if account is already locked', function () {
        $user = UserFactory::new()->locked()->create();

        app(LockUserAccountAction::class)->execute($user, 'another_reason');

        expect($user->fresh()->locked_at)->not->toBeNull();
    });
});
