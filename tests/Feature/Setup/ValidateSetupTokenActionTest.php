<?php

declare(strict_types=1);

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Setup\Actions\GenerateSetupTokenAction;
use App\Domain\Setup\Actions\ValidateSetupTokenAction;
use App\Domain\Setup\Models\Setup;

describe('ValidateSetupTokenAction', function () {
    it('validates a correct token', function () {
        $tokenData = app(GenerateSetupTokenAction::class)->execute();

        $action = app(ValidateSetupTokenAction::class);
        $action->execute($tokenData['plaintext']);

        expect(true)->toBeTrue();
    });

    it('throws for incorrect token', function () {
        app(GenerateSetupTokenAction::class)->execute();

        $action = app(ValidateSetupTokenAction::class);

        $action->execute('wrong-token');
    })->throws(RuntimeException::class);

    it('throws when no setup record exists', function () {
        Setup::truncate();

        $action = app(ValidateSetupTokenAction::class);
        $action->execute('some-token');
    })->throws(RuntimeException::class);

    it('throws for expired token', function () {
        $tokenData = app(GenerateSetupTokenAction::class)->execute();

        $setup = Setup::first();
        $setup->forceFill(['token_expires_at' => now()->subHour()])->save();

        $action = app(ValidateSetupTokenAction::class);
        $action->execute($tokenData['plaintext']);
    })->throws(RuntimeException::class);

    it('extends BaseAction', function () {
        expect(ValidateSetupTokenAction::class)->toExtend(BaseAction::class);
    });

    it('has execute method', function () {
        expect(ValidateSetupTokenAction::class)->toHaveMethod('execute');
    });
});
