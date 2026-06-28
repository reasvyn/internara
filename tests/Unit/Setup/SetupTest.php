<?php

declare(strict_types=1);

namespace Tests\Unit\Setup\Models;

use App\Settings\Services\Settings;
use App\Setup\Entities\SetupEntity;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('setup entity from settings returns SetupEntity', function () {
    Settings::set([
        'setup.is_installed' => ['value' => true, 'group' => 'setup', 'type' => 'boolean'],
        'setup.completed_steps' => [
            'value' => ['step1', 'step2'],
            'group' => 'setup',
            'type' => 'json',
        ],
    ]);

    $state = SetupEntity::get();

    expect($state)->toBeInstanceOf(SetupEntity::class);
    expect($state->isInstalled())->toBeTrue();
    expect($state->isStepCompleted('step1'))->toBeTrue();
    expect($state->isStepCompleted('step2'))->toBeTrue();
    expect($state->isStepCompleted('step3'))->toBeFalse();
});
