<?php

declare(strict_types=1);

namespace Tests\Unit\Setup\Models;

use Tests\Support\WithSettingsSeed;
use App\Setup\Entities\SetupEntity;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);
uses(WithSettingsSeed::class);

test('setup entity from settings returns SetupEntity', function () {
    $this->seedSettings([
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
