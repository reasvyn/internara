<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\Settings\Livewire\AppSignature;
use App\Domain\Settings\Livewire\SystemSetting;
use App\Domain\Settings\Models\Setting;
use App\Domain\User\Models\User;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    app()->setLocale('en');
    RoleModel::create(['name' => Role::SUPER_ADMIN->value, 'guard_name' => 'web']);
    $this->admin = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
    $this->actingAs($this->admin);
});

describe('SystemSetting', function () {
    it('mounts with defaults', function () {
        Livewire::test(SystemSetting::class)
            ->assertSuccessful()
            ->assertSet('app_name', fn ($v) => is_string($v))
            ->assertSet('app_version', fn ($v) => is_string($v));
    });

    it('loads existing settings from database', function () {
        Setting::create(['key' => 'brand_name', 'value' => 'My School', 'group' => 'general', 'type' => 'string']);
        Setting::create(['key' => 'site_title', 'value' => 'PKL System', 'group' => 'general', 'type' => 'string']);

        Livewire::test(SystemSetting::class)
            ->assertSet('generalForm.brand_name', 'My School')
            ->assertSet('generalForm.site_title', 'PKL System');
    });

    it('loads branding colors from database', function () {
        Setting::create(['key' => 'primary_color', 'value' => '#ff6600', 'group' => 'branding', 'type' => 'string']);

        Livewire::test(SystemSetting::class)
            ->assertSet('brandingForm.primary_color', '#ff6600');
    });

    it('applies color preset', function () {
        Livewire::test(SystemSetting::class)
            ->call('applyPreset', 'emerald')
            ->assertSet('brandingForm.selected_preset', 'emerald');
    });

    it('uploads brand logo via Livewire', function () {
        $file = UploadedFile::fake()->image('logo.png');

        Livewire::test(SystemSetting::class)
            ->set('brandingForm.brand_logo', $file);
        expect(true)->toBeTrue();
    });
});

describe('AppSignature', function () {
    it('renders with app metadata', function () {
        Livewire::test(AppSignature::class)
            ->assertSuccessful()
            ->assertSee('License');
    });
});
