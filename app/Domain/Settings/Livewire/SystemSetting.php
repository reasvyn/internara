<?php

declare(strict_types=1);

namespace App\Domain\Settings\Livewire;

use App\Domain\Core\Support\SmartLogger;
use App\Domain\Settings\Actions\BatchSetSettingAction;
use App\Domain\Settings\Actions\TestMailSettingsAction;
use App\Domain\Settings\Actions\UploadBrandAssetAction;
use App\Domain\Settings\Support\Settings;
use App\Domain\Shared\Support\Theme;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class SystemSetting extends Component
{
    use WithFileUploads;

    public function boot(): void
    {
        abort_unless(auth()->user()->hasRole('super_admin'), 403);
    }

    public string $brand_name = '';

    public string $site_title = '';

    public string $default_locale = 'id';

    public string $app_name = '';

    public string $app_version = '';

    public string $active_academic_year = '';

    public string $mail_from_address = '';

    public string $mail_from_name = '';

    public string $mail_host = '';

    public string $mail_port = '587';

    public string $mail_encryption = 'tls';

    public string $mail_username = '';

    public string $mail_password = '';

    public $brand_logo;

    public $site_favicon;

    public string $primary_color = '';

    public string $secondary_color = '';

    public string $accent_color = '';

    public string $base_color = '';

    public ?string $selected_preset = null;

    public ?string $current_logo_url = null;

    public ?string $current_favicon_url = null;

    public function mount(): void
    {
        $this->brand_name = Settings::get('brand_name', brand('name'));
        $this->site_title = Settings::get('site_title', brand('site_title'));
        $this->default_locale = Settings::get('default_locale', 'id');

        $this->app_name = Settings::get('app_name', app_info('name'));
        $this->app_version = Settings::get('app_version', app_info('version'));

        $this->current_logo_url = Settings::get('brand_logo');
        $this->current_favicon_url = Settings::get('site_favicon');

        $defaults = Theme::defaults();
        $this->primary_color = Settings::get('primary_color', $defaults['primary']);
        $this->secondary_color = Settings::get('secondary_color', $defaults['secondary']);
        $this->accent_color = Settings::get('accent_color', $defaults['accent']);
        $this->base_color = Settings::get('base_color', $defaults['base']);

        $this->selected_preset = $this->detectPreset();

        $this->active_academic_year = Settings::get(
            'active_academic_year',
            date('Y').'/'.(date('Y') + 1),
        );

        $this->mail_from_address = Settings::get('mail_from_address', '');
        $this->mail_from_name = Settings::get('mail_from_name', '');
        $this->mail_host = Settings::get('mail_host', '');
        $this->mail_port = Settings::get('mail_port', '587');
        $this->mail_encryption = Settings::get('mail_encryption', 'tls');
        $this->mail_username = Settings::get('mail_username', '');
        $this->mail_password = '';
    }

    public function detectPreset(): ?string
    {
        $current = [
            'primary' => $this->primary_color,
            'secondary' => $this->secondary_color,
            'accent' => $this->accent_color,
            'base' => $this->base_color,
        ];

        foreach (Theme::presets() as $key => $preset) {
            $presetColors = $preset['colors'];

            if ($presetColors['primary'] === $current['primary']
                && $presetColors['secondary'] === $current['secondary']
                && $presetColors['accent'] === $current['accent']
                && $presetColors['base'] === $current['base']) {
                return $key;
            }
        }

        return null;
    }

    public function applyPreset(string $key): void
    {
        $presets = Theme::presets();

        if (! isset($presets[$key])) {
            return;
        }

        $this->primary_color = $presets[$key]['colors']['primary'];
        $this->secondary_color = $presets[$key]['colors']['secondary'];
        $this->accent_color = $presets[$key]['colors']['accent'];
        $this->base_color = $presets[$key]['colors']['base'];
        $this->selected_preset = $key;
    }

    public function rules(): array
    {
        return [
            'brand_name' => 'required|string|max:50',
            'site_title' => 'required|string|max:100',
            'default_locale' => 'required|in:id,en',

            'brand_logo' => 'nullable|image|max:1024',
            'site_favicon' => 'nullable|image|max:512',

            'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'base_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],

            'active_academic_year' => 'required|string|regex:/^\d{4}\/\d{4}$/',

            'mail_from_address' => 'nullable|email',
            'mail_from_name' => 'nullable|string|max:100',
            'mail_host' => 'nullable|string',
            'mail_port' => 'nullable|numeric',
            'mail_encryption' => 'nullable|in:tls,ssl,none',
            'mail_username' => 'nullable|string',
            'mail_password' => 'nullable|string',
        ];
    }

    public function brandLogoPreviewUrl(): ?string
    {
        if ($this->brand_logo === null) {
            return null;
        }

        try {
            return $this->brand_logo->temporaryUrl();
        } catch (\Exception $e) {
            SmartLogger::warning('Failed to generate brand logo preview')
                ->withPayload(['error' => $e->getMessage()])
                ->systemOnly()
                ->save();

            return null;
        }
    }

    public function faviconPreviewUrl(): ?string
    {
        if ($this->site_favicon === null) {
            return null;
        }

        try {
            return $this->site_favicon->temporaryUrl();
        } catch (\Exception $e) {
            SmartLogger::warning('Failed to generate favicon preview')
                ->withPayload(['error' => $e->getMessage()])
                ->systemOnly()
                ->save();

            return null;
        }
    }

    public function save(BatchSetSettingAction $batchSetSetting, UploadBrandAssetAction $uploadBrand): void
    {
        $validated = $this->validate();

        $settings = [
            'brand_name' => $this->brand_name,
            'site_title' => $this->site_title,
            'default_locale' => $this->default_locale,
            'active_academic_year' => $this->active_academic_year,
            'mail_from_address' => $this->mail_from_address,
            'mail_from_name' => $this->mail_from_name,
            'mail_host' => $this->mail_host,
            'mail_port' => $this->mail_port,
            'mail_encryption' => $this->mail_encryption,
            'mail_username' => $this->mail_username,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'accent_color' => $this->accent_color,
            'base_color' => $this->base_color,
        ];

        if ($this->mail_password !== '') {
            $settings['mail_password'] = [
                'value' => $this->mail_password,
                'type' => 'encrypted',
            ];
        }

        if ($this->brand_logo) {
            $settings['brand_logo'] = $uploadBrand->execute($this->brand_logo);
        }

        if ($this->site_favicon) {
            $settings['site_favicon'] = $uploadBrand->execute($this->site_favicon, 'favicon');
        }

        $batchSetSetting->execute($settings);

        SmartLogger::info('settings_updated')
            ->event('settings_updated')
            ->module('Setting')
            ->withPayload(['keys' => array_keys($settings)])
            ->activityOnly()
            ->save();

        flash()->success(__('setting.messages.saved'));

        $this->redirectRoute('admin.settings', navigate: true);
    }

    public function testEmail(TestMailSettingsAction $action): void
    {
        $this->validate([
            'mail_host' => 'required',
            'mail_port' => 'required|numeric',
            'mail_username' => 'required',
            'mail_password' => 'required',
            'mail_from_address' => 'required|email',
        ]);

        $sent = $action->execute(
            auth()->user()->email,
            [
                'host' => $this->mail_host,
                'port' => $this->mail_port,
                'encryption' => $this->mail_encryption,
                'username' => $this->mail_username,
                'password' => $this->mail_password,
                'from_address' => $this->mail_from_address,
                'from_name' => $this->mail_from_name,
            ],
        );

        if ($sent) {
            flash()->success(__('setting.messages.test_email_sent'));
        } else {
            flash()->error(__('setting.messages.test_email_failed'));
        }
    }

    #[Layout('layouts::app', ['title' => 'System Settings'])]
    public function render(): View
    {
        return view('settings.system-setting');
    }
}
