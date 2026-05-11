<?php

declare(strict_types=1);

use App\Actions\Setup\GenerateSetupTokenAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('generates a setup token', function () {
        $result = app(GenerateSetupTokenAction::class)->execute();

        expect($result)->toHaveKeys(['plaintext', 'expires_at'])
            ->and($result['plaintext'])->toBeString()
            ->and($result['expires_at'])->toBeInstanceOf(Carbon::class);
    });
});
