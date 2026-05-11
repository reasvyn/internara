<?php

declare(strict_types=1);

use App\Actions\User\UpdateProfileAction;
use App\Models\Profile;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('updates user profile', function () {
        $user = UserFactory::new()->create();
        $user->profile()->create([]);

        $profile = app(UpdateProfileAction::class)->execute($user, [
            'phone' => '08123456789',
            'address' => '123 Main St',
        ]);

        expect($profile)->toBeInstanceOf(Profile::class)
            ->and($profile->phone)->toBe('08123456789')
            ->and($profile->address)->toBe('123 Main St');
    });

    it('creates profile if none exists', function () {
        $user = UserFactory::new()->create();

        $profile = app(UpdateProfileAction::class)->execute($user, [
            'phone' => '08123456789',
        ]);

        expect($profile)->toBeInstanceOf(Profile::class)
            ->and($profile->phone)->toBe('08123456789');
    });

    it('returns existing profile when no data provided', function () {
        $user = UserFactory::new()->create();
        $user->profile()->create(['phone' => '0811111111']);

        $profile = app(UpdateProfileAction::class)->execute($user, []);

        expect($profile->phone)->toBe('0811111111');
    });
});
