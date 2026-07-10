<?php

declare(strict_types=1);

use Tests\Support\WithSettingsSeed;
use App\Setup\SetupWizard\Livewire\SetupWizard;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);
uses(WithSettingsSeed::class);

beforeEach(function () {
    $this->seedSettings([
        'setup.is_installed' => ['value' => false, 'group' => 'setup', 'type' => 'boolean'],
        'setup.install_token' => ['value' => null, 'group' => 'setup', 'type' => 'string'],
        'setup.token_expires_at' => ['value' => null, 'group' => 'setup', 'type' => 'datetime'],
        'setup.completed_steps' => ['value' => [], 'group' => 'setup', 'type' => 'json'],
        'setup.install_recovery_key' => ['value' => null, 'group' => 'setup', 'type' => 'string'],
        'setup.token_version' => ['value' => 0, 'group' => 'setup', 'type' => 'integer'],
    ]);
});

test('renders within setup wizard', function () {
    Livewire::test(SetupWizard::class)->assertSuccessful();
});
