<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Domain\Setup\Actions\InstallSystemAction;
use App\Domain\Setup\Models\Setup;

beforeEach(function () {
    Setup::truncate();
    Setup::create(['is_installed' => false, 'completed_steps' => []]);

    config(['setup.requirements.extensions' => []]);
    config(['setup.requirements.recommended_extensions' => []]);
    config(['setup.requirements.php_version' => PHP_VERSION]);
});

describe('InstallSystemAction', function () {
    it('executes full installation and returns token data', function () {
        $result = app(InstallSystemAction::class)->execute();

        expect($result)->toHaveKeys(['plaintext', 'expires_at'])
            ->and($result['plaintext'])->toBeString()->not->toBeEmpty();
    });

    it('creates a setup record with token and expiration', function () {
        app(InstallSystemAction::class)->execute();

        $setup = Setup::first();

        expect($setup->setup_token)->not->toBeNull()
            ->and($setup->token_expires_at)->not->toBeNull();
    });

    it('throws RuntimeException when environment audit fails', function () {
        config(['database.connections.sqlite.username' => 'forge']);

        app(InstallSystemAction::class)->execute();
    })->throws(RuntimeException::class, 'System audit check failed.');
});
