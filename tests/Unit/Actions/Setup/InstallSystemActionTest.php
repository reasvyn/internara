<?php

declare(strict_types=1);

use App\Actions\Setup\InstallSystemAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('returns token data when audit passes', function () {
        $result = app(InstallSystemAction::class)->execute();

        expect($result)->toBeArray()
            ->toHaveKey('plaintext')
            ->toHaveKey('expires_at');
    });
});
