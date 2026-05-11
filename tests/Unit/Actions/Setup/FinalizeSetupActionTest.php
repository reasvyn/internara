<?php

declare(strict_types=1);

use App\Actions\Setup\FinalizeSetupAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('finalizes setup and returns a recovery key', function () {
        $recoveryKey = app(FinalizeSetupAction::class)->execute();

        expect($recoveryKey)->toBeString();
    });
});
