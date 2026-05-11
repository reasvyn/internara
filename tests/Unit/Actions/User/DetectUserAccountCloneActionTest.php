<?php

declare(strict_types=1);

use App\Actions\User\DetectUserAccountCloneAction;
use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('detects duplicate accounts with same email', function () {
        $first = UserFactory::new()->create(['email' => 'dupe@example.com']);

        $userData = $first->toArray();
        $userData['id'] = (string) Str::uuid();
        $userData['username'] = 'seconduser';
        User::insert($userData);

        $result = app(DetectUserAccountCloneAction::class)->execute();

        expect($result)->toHaveCount(1)
            ->and($result->first()['type'])->toBe('duplicate_email')
            ->and($result->first()['identifier'])->toBe('dupe@example.com')
            ->and($result->first()['user_ids'])->toHaveCount(2);
    });

    it('returns empty collection when no duplicates', function () {
        UserFactory::new()->create(['email' => 'one@example.com']);
        UserFactory::new()->create(['email' => 'two@example.com']);

        $result = app(DetectUserAccountCloneAction::class)->execute();

        expect($result)->toBeEmpty();
    });
});
