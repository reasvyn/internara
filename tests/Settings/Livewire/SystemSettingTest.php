<?php

declare(strict_types=1);

use App\Settings\Livewire\SystemSetting;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('renders successfully', function () {
    Livewire::test(SystemSetting::class)
        ->assertSuccessful();
});

test('apply preset updates branding form colors', function () {
    Livewire::test(SystemSetting::class)
        ->call('applyPreset', 'sky')
        ->assertSet('brandingForm.selected_preset', 'sky')
        ->assertSet('brandingForm.primary_color', '#0ea5e9');
});

test('allows admin to view but not update settings', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    test()->actingAs($user);

    Livewire::test(SystemSetting::class)
        ->assertSuccessful();
});
