<?php

declare(strict_types=1);

namespace App\Settings\Livewire;

use App\Academics\AcademicYear\Actions\ActivateAcademicYearAction;
use App\Settings\Actions\ReadAcademicYearAction;
use App\Settings\Actions\SaveSystemSettingsAction;
use App\Settings\Actions\TestMailSettingsAction;
use App\Settings\Branding\Actions\RemoveBrandAssetAction;
use App\Settings\Actions\SetSettingAction;
use App\Settings\Branding\Actions\UploadBrandAssetAction;
use App\Settings\Services\Settings;
use App\Settings\Branding\Livewire\Forms\BrandingForm;
use App\Settings\Data\SystemSettingsData;
use App\Settings\Livewire\Forms\GeneralSettingsForm;
use App\Settings\Livewire\Forms\MailSettingsForm;
use App\Settings\Models\Setting;
use App\Settings\Theme\Support\Theme;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class SystemSetting extends Component
{
    use WithFileUploads;

    public GeneralSettingsForm $generalForm;

    public BrandingForm $brandingForm;

    public MailSettingsForm $mailSettingsForm;

    public string $app_name = '';

    public string $app_version = '';

    public bool $showConfirm = false;

    public ?string $confirmTarget = null;

    private ?ReadAcademicYearAction $readYearAction = null;

    public function boot(ReadAcademicYearAction $action): void
    {
        $this->readYearAction = $action;
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Setting::class);

        $defaults = Theme::defaults();

        $this->app_name = Settings::get('app_name', app_info('name'));
        $this->app_version = Settings::get('app_version', app_info('version'));

        $this->generalForm->brand_name = Settings::get('brand_name', brand('name'));
        $this->generalForm->site_title = Settings::get('site_title', brand('site_title'));
        $this->generalForm->default_locale = Settings::get('default_locale', 'id');
        $this->generalForm->active_academic_year = Settings::get(
            'active_academic_year',
            date('Y') . '/' . (date('Y') + 1),
        );

        $this->brandingForm->primary_color = Settings::get('primary_color', $defaults['primary']);
        $this->brandingForm->secondary_color = Settings::get(
            'secondary_color',
            $defaults['secondary'],
        );
        $this->brandingForm->accent_color = Settings::get('accent_color', $defaults['accent']);
        $this->brandingForm->base_color = Settings::get('base_color', $defaults['base']);
        $this->brandingForm->selected_preset = $this->brandingForm->detectPreset();
        $this->brandingForm->current_logo_url = Settings::get('brand_logo');
        $this->brandingForm->current_favicon_url = Settings::get('site_favicon');

        $this->mailSettingsForm->mail_from_address = Settings::get('mail_from_address', '');
        $this->mailSettingsForm->mail_from_name = Settings::get('mail_from_name', '');
        $this->mailSettingsForm->mail_host = Settings::get('mail_host', '');
        $this->mailSettingsForm->mail_port = Settings::get('mail_port', '587');
        $this->mailSettingsForm->mail_encryption = Settings::get('mail_encryption', 'tls');
        $this->mailSettingsForm->mail_username = Settings::get('mail_username', '');
    }

    #[Computed]
    public function academicYears(): Collection
    {
        return $this->readYearAction?->execute() ?? collect();
    }

    #[Computed]
    public function academicYearOptions(): array
    {
        return $this->academicYears
            ->map(
                fn($year) => [
                    'id' => $year->name,
                    'name' => $year->name,
                ],
            )
            ->toArray();
    }

    public function applyPreset(string $key): void
    {
        $this->brandingForm->applyPreset($key);
    }

    public function updatedBrandingFormBrandLogo(
        UploadBrandAssetAction $uploadBrand,
        SetSettingAction $setSetting,
    ): void {
        $this->authorize('update', Setting::class);
        $this->brandingForm->validate(['brand_logo' => 'nullable|image|max:1024']);

        if ($this->brandingForm->brand_logo instanceof UploadedFile) {
            $url = $uploadBrand->execute($this->brandingForm->brand_logo);
            $setSetting->execute(key: 'brand_logo', value: $url, group: 'branding');
            $this->brandingForm->current_logo_url = $url;
            flash()->success(__('setting.messages.logo_saved'));
        }
    }

    public function updatedBrandingFormSiteFavicon(
        UploadBrandAssetAction $uploadBrand,
        SetSettingAction $setSetting,
    ): void {
        $this->authorize('update', Setting::class);
        $this->brandingForm->validate(['site_favicon' => 'nullable|image|max:512']);

        if ($this->brandingForm->site_favicon instanceof UploadedFile) {
            $url = $uploadBrand->execute($this->brandingForm->site_favicon, 'favicon');
            $setSetting->execute(key: 'site_favicon', value: $url, group: 'branding');
            $this->brandingForm->current_favicon_url = $url;
            flash()->success(__('setting.messages.favicon_saved'));
        }
    }

    public function confirmRemoveBrandLogo(RemoveBrandAssetAction $action): void
    {
        $this->authorize('update', Setting::class);

        $action->execute('logo');

        $this->brandingForm->current_logo_url = null;
        $this->brandingForm->brand_logo = null;

        flash()->success(__('setting.messages.logo_removed'));
    }

    public function confirmRemoveFavicon(RemoveBrandAssetAction $action): void
    {
        $this->authorize('update', Setting::class);

        $action->execute('favicon');

        $this->brandingForm->current_favicon_url = null;
        $this->brandingForm->site_favicon = null;

        flash()->success(__('setting.messages.favicon_removed'));
    }

    public function confirmAction(): void
    {
        match ($this->confirmTarget) {
            'removeBrandLogo' => $this->confirmRemoveBrandLogo(),
            'removeFavicon' => $this->confirmRemoveFavicon(),
            default => null,
        };

        $this->showConfirm = false;
        $this->confirmTarget = null;
    }

    public function save(
        SaveSystemSettingsAction $action,
        ActivateAcademicYearAction $activateYear,
    ): void {
        $this->authorize('update', Setting::class);

        $this->generalForm->validate();
        $this->brandingForm->validate();
        $this->mailSettingsForm->validate();

        $data = new SystemSettingsData(
            brandName: $this->generalForm->brand_name,
            siteTitle: $this->generalForm->site_title,
            defaultLocale: $this->generalForm->default_locale,
            activeAcademicYear: $this->generalForm->active_academic_year,
            primaryColor: $this->brandingForm->primary_color,
            secondaryColor: $this->brandingForm->secondary_color,
            accentColor: $this->brandingForm->accent_color,
            baseColor: $this->brandingForm->base_color,
            brandLogo: $this->brandingForm->brand_logo,
            siteFavicon: $this->brandingForm->site_favicon,
            mailFromAddress: $this->mailSettingsForm->mail_from_address,
            mailFromName: $this->mailSettingsForm->mail_from_name,
            mailHost: $this->mailSettingsForm->mail_host,
            mailPort: $this->mailSettingsForm->mail_port,
            mailEncryption: $this->mailSettingsForm->mail_encryption,
            mailUsername: $this->mailSettingsForm->mail_username,
            mailPassword: $this->mailSettingsForm->mail_password ?: null,
        );

        $action->execute($data);

        $selectedYear = $this->generalForm->active_academic_year;

        if ($selectedYear) {
            $year = $this->readYearAction?->findByName($selectedYear);

            if ($year && $year->asAcademicYearState()->canBeActivated()) {
                $activateYear->execute($year);
            }
        }

        flash()->success(__('setting.messages.saved'));
    }

    public function testEmail(TestMailSettingsAction $action): void
    {
        $this->authorize('update', Setting::class);

        $this->mailSettingsForm->validate([
            'mail_host' => 'required',
            'mail_port' => 'required|numeric',
            'mail_username' => 'required',
            'mail_password' => 'required',
            'mail_from_address' => 'required|email',
        ]);

        $sent = $action->execute(auth()->user()->email, $this->mailSettingsForm->toMailConfig());

        if ($sent) {
            flash()->success(__('setting.messages.test_email_sent'));
        } else {
            flash()->error(__('setting.messages.test_email_failed'));
        }
    }

    public function title(): string
    {
        return __('setting.title');
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        return view('settings.system-setting', [
            'presets' => Theme::presets(),
        ]);
    }
}
