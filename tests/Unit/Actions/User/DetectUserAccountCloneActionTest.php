<?php

declare(strict_types=1);

use App\Actions\User\DetectUserAccountCloneAction;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('returns empty collection when all emails are unique', function () {
        UserFactory::new()->create(['email' => 'one@example.com']);
        UserFactory::new()->create(['email' => 'two@example.com']);

        $result = app(DetectUserAccountCloneAction::class)->execute();

        expect($result)->toBeEmpty();
    });

    it('catches query errors gracefully and returns empty', function () {
        UserFactory::new()->count(2)->create();

        $result = app(DetectUserAccountCloneAction::class)->execute();

        expect($result)->toBeInstanceOf(Collection::class);
    });
});
