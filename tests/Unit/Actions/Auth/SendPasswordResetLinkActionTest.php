<?php

declare(strict_types=1);

use App\Actions\Auth\SendPasswordResetLinkAction;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('returns a status string when called', function () {
        UserFactory::new()->create(['email' => 'test@example.com']);

        $status = app(SendPasswordResetLinkAction::class)->execute('test@example.com');

        expect($status)->toBeString();
    });
});
