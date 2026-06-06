<?php

declare(strict_types=1);

namespace Tests\Unit\Setup\Models;

use App\Setup\Entities\SetupState;
use App\SysAdmin\Settings\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('setup state from settings returns SetupState entity', function () {
    Settings::set([
        'setup.is_installed' => ['value' => true, 'group' => 'setup', 'type' => 'boolean'],
        'setup.completed_steps' => ['value' => ['step1', 'step2'], 'group' => 'setup', 'type' => 'json'],
    ]);

    $state = SetupState::fromSettings();

    expect($state)->toBeInstanceOf(SetupState::class);
    expect($state->isInstalled())->toBeTrue();
    expect($state->isStepCompleted('step1'))->toBeTrue();
    expect($state->isStepCompleted('step2'))->toBeTrue();
    expect($state->isStepCompleted('step3'))->toBeFalse();
});
