<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Setup\Actions\GenerateSetupTokenAction;
use Carbon\Carbon;

describe('GenerateSetupTokenAction', function () {
    it('generates token with plaintext and expiration', function () {
        $result = app(GenerateSetupTokenAction::class)->execute();

        expect($result)->toHaveKeys(['plaintext', 'expires_at'])
            ->and($result['plaintext'])->toBeString()->not->toBeEmpty()
            ->and($result['expires_at'])->toBeInstanceOf(Carbon::class);
    });

    it('generates token of configured length', function () {
        config(['setup.token.length' => 32]);

        $result = app(GenerateSetupTokenAction::class)->execute();

        expect(strlen($result['plaintext']))->toBe(32);
    });

    it('extends BaseAction', function () {
        expect(GenerateSetupTokenAction::class)->toExtend(BaseAction::class);
    });

    it('has execute method', function () {
        expect(GenerateSetupTokenAction::class)->toHaveMethod('execute');
    });
});
