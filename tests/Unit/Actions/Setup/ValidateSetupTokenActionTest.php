<?php

declare(strict_types=1);

use App\Actions\Setup\GenerateSetupTokenAction;
use App\Actions\Setup\ValidateSetupTokenAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('validates a correct token', function () {
        $token = app(GenerateSetupTokenAction::class)->execute();

        app(ValidateSetupTokenAction::class)->execute($token['plaintext']);

        expect(true)->toBeTrue();
    });

    it('throws RuntimeException for invalid token', function () {
        expect(fn () => app(ValidateSetupTokenAction::class)->execute('invalid-token'))
            ->toThrow(RuntimeException::class, 'Invalid setup token.');
    });
});
