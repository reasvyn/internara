<?php

declare(strict_types=1);

use App\Actions\User\UnlockUserAccountAction;
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
    it('unlocks a locked user account', function () {
        $user = UserFactory::new()->locked()->create();

        app(UnlockUserAccountAction::class)->execute($user);

        expect($user->fresh()->locked_at)->toBeNull();
    });

    it('does nothing if account is not locked', function () {
        $user = UserFactory::new()->create();

        app(UnlockUserAccountAction::class)->execute($user);

        expect($user->fresh()->locked_at)->toBeNull();
    });
});
