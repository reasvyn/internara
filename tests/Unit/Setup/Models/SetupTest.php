<?php

declare(strict_types=1);

namespace Tests\Unit\Setup\Models;

use App\Setup\Entities\SetupState;
use App\Setup\Models\Setup;

test('setup model asSetupState returns SetupState entity', function () {
    $setup = new Setup;
    $setup->is_installed = true;
    $setup->completed_steps = ['step1', 'step2'];

    $state = $setup->asSetupState();

    expect($state)->toBeInstanceOf(SetupState::class);
    expect($state->isInstalled())->toBeTrue();
    expect($state->isStepCompleted('step1'))->toBeTrue();
    expect($state->isStepCompleted('step2'))->toBeTrue();
    expect($state->isStepCompleted('step3'))->toBeFalse();
});
