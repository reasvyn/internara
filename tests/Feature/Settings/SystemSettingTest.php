<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Livewire\Admin\SystemSetting;
use App\Models\User;
use App\Support\Settings;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

test('system settings page renders for authenticated user', function () {
    $user = User::factory()->create();
    Role::create(['name' => 'super_admin']);
    $user->assignRole('super_admin');

    Livewire::actingAs($user)
        ->test(SystemSetting::class)
        ->assertSet('brand_name', 'Internara')
        ->assertSet('default_locale', 'id');
});

test('admin can save system settings', function () {
    $user = User::factory()->create();
    Role::create(['name' => 'super_admin']);
    $user->assignRole('super_admin');

    Livewire::actingAs($user)
        ->test(SystemSetting::class)
        ->set('brand_name', 'New Brand')
        ->set('site_title', 'New Site Title')
        ->set('default_locale', 'en')
        ->set('active_academic_year', '2026/2027')
        ->set('attendance_check_in_start', '06:00')
        ->set('attendance_late_threshold', '07:30')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.settings'));
});

test('system settings validates required fields', function () {
    $user = User::factory()->create();
    Role::create(['name' => 'super_admin']);
    $user->assignRole('super_admin');

    Livewire::actingAs($user)
        ->test(SystemSetting::class)
        ->set('brand_name', '')
        ->set('site_title', '')
        ->call('save')
        ->assertHasErrors(['brand_name' => 'required', 'site_title' => 'required']);
});

test('system settings validates academic year format', function () {
    $user = User::factory()->create();
    Role::create(['name' => 'super_admin']);
    $user->assignRole('super_admin');

    Livewire::actingAs($user)
        ->test(SystemSetting::class)
        ->set('active_academic_year', 'invalid-format')
        ->call('save')
        ->assertHasErrors(['active_academic_year' => 'regex']);
});

test('system settings validates locale options', function () {
    $user = User::factory()->create();
    Role::create(['name' => 'super_admin']);
    $user->assignRole('super_admin');

    Livewire::actingAs($user)
        ->test(SystemSetting::class)
        ->set('default_locale', 'fr')
        ->call('save')
        ->assertHasErrors(['default_locale' => 'in']);
});

test('saved settings persist after redirect', function () {
    $user = User::factory()->create();
    Role::create(['name' => 'super_admin']);
    $user->assignRole('super_admin');

    Livewire::actingAs($user)
        ->test(SystemSetting::class)
        ->set('brand_name', 'Persisted Brand')
        ->set('site_title', 'Persisted Title')
        ->call('save');

    expect(Settings::get('brand_name'))->toBe('Persisted Brand');
    expect(Settings::get('site_title'))->toBe('Persisted Title');
});

test('mail settings are saved correctly', function () {
    $user = User::factory()->create();
    Role::create(['name' => 'super_admin']);
    $user->assignRole('super_admin');

    Livewire::actingAs($user)
        ->test(SystemSetting::class)
        ->set('mail_from_address', 'test@example.com')
        ->set('mail_host', 'smtp.example.com')
        ->set('mail_port', '465')
        ->set('mail_encryption', 'ssl')
        ->call('save')
        ->assertHasNoErrors();

    expect(Settings::get('mail_from_address'))->toBe('test@example.com');
    expect(Settings::get('mail_host'))->toBe('smtp.example.com');
    expect(Settings::get('mail_port'))->toBe('465');
});
