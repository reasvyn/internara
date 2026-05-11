<?php

declare(strict_types=1);

use App\Actions\Setup\ResetSetupStateAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('resets setup state and generates new token', function () {
        $result = app(ResetSetupStateAction::class)->execute();

        expect($result)->toHaveKeys(['plaintext', 'expires_at'])
            ->and($result['plaintext'])->toBeString();
    });
});
