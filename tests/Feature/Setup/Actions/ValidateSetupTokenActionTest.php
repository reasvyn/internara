<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Actions;

use App\Domain\Setup\Actions\ValidateSetupTokenAction;
use App\Domain\Setup\Models\Setup;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Setup::query()->delete();
});

describe('ValidateSetupTokenAction', function () {
    it('validates a correct token', function () {
        $plaintext = 'valid-token-content';
        $setup = Setup::factory()->create([
            'setup_token' => Crypt::encryptString($plaintext),
            'token_expires_at' => now()->addHour(),
        ]);

        app(ValidateSetupTokenAction::class)->execute($plaintext);

        expect(true)->toBeTrue();
    });

    it('throws for non-existent setup', function () {
        Setup::query()->delete();

        app(ValidateSetupTokenAction::class)->execute('any-token');
    })->throws(RuntimeException::class, 'Invalid setup token.');

    it('throws for expired token', function () {
        $plaintext = 'expired-token';
        $setup = Setup::factory()->create([
            'setup_token' => Crypt::encryptString($plaintext),
            'token_expires_at' => now()->subMinute(),
        ]);

        app(ValidateSetupTokenAction::class)->execute($plaintext);
    })->throws(RuntimeException::class, 'Invalid setup token.');

    it('throws for wrong token', function () {
        $setup = Setup::factory()->create([
            'setup_token' => Crypt::encryptString('real-token'),
            'token_expires_at' => now()->addHour(),
        ]);

        app(ValidateSetupTokenAction::class)->execute('wrong-token');
    })->throws(RuntimeException::class, 'Invalid setup token.');

    it('throws when token is null in database', function () {
        $setup = Setup::factory()->create([
            'setup_token' => null,
            'token_expires_at' => now()->addHour(),
        ]);

        app(ValidateSetupTokenAction::class)->execute('any-token');
    })->throws(RuntimeException::class, 'Invalid setup token.');

    it('consumes the token after successful validation', function () {
        $plaintext = 'consumable-token';
        $setup = Setup::factory()->create([
            'setup_token' => Crypt::encryptString($plaintext),
            'token_expires_at' => now()->addHour(),
        ]);

        app(ValidateSetupTokenAction::class)->execute($plaintext);

        $setup->refresh();
        expect($setup->setup_token)->toBeNull()
            ->and($setup->token_expires_at)->toBeNull();
    });

    it('throws if decryption fails', function () {
        $setup = Setup::factory()->create([
            'setup_token' => 'not-valid-encrypted-data',
            'token_expires_at' => now()->addHour(),
        ]);

        app(ValidateSetupTokenAction::class)->execute('any-token');
    })->throws(RuntimeException::class, 'Invalid setup token.');
});
