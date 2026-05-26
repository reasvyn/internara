<?php

declare(strict_types=1);

namespace App\Domain\Settings\Livewire;

use App\Domain\School\Actions\ActivateAcademicYearAction;
use App\Domain\School\Models\AcademicYear;
use App\Domain\Settings\Actions\GetAcademicYearsAction;
use App\Domain\Settings\Actions\SaveSystemSettingsAction;
use App\Domain\Settings\Actions\TestMailSettingsAction;
use App\Domain\Settings\Livewire\Forms\BrandingForm;
use App\Domain\Settings\Livewire\Forms\GeneralSettingsForm;
use App\Domain\Settings\Livewire\Forms\MailSettingsForm;
use App\Domain\Settings\Models\Setting;
use App\Domain\Settings\Support\Settings;
use App\Domain\Shared\Support\Theme;
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

    public function mount(): void
    {
        $this->authorize('viewAny', Setting::class);

        $this->app_name = Settings::get('app_name', app_info('name'));
        $this->app_version = Settings::get('app_version', app_info('version'));

        $defaults = Theme::defaults();

        $this->generalForm->brand_name = Settings::get('brand_name', brand('name'));
        $this->generalForm->site_title = Settings::get('site_title', brand('site_title'));
        $this->generalForm->default_locale = Settings::get('default_locale', 'id');
        $this->generalForm->active_academic_year = Settings::get(
            'active_academic_year',
            date('Y').'/'.(date('Y') + 1),
        );

        $this->brandingForm->primary_color = Settings::get('primary_color', $defaults['primary']);
        $this->brandingForm->secondary_color = Settings::get('secondary_color', $defaults['secondary']);
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
        return app(GetAcademicYearsAction::class)->execute();
    }

    #[Computed]
    public function academicYearOptions(): array
    {
        return $this->academicYears->map(fn ($year) => [
            'id' => $year->name,
            'name' => $year->name,
        ])->toArray();
    }

    public function applyPreset(string $key): void
    {
        $this->brandingForm->applyPreset($key);
    }

    public function save(
        SaveSystemSettingsAction $action,
        ActivateAcademicYearAction $activateYear,
    ): void {
        $this->authorize('update', Setting::class);

        $this->generalForm->validate();
        $this->brandingForm->validate();
        $this->mailSettingsForm->validate();

        $selectedYear = $this->generalForm->active_academic_year;

        $action->execute(
            general: [
                'brand_name' => $this->generalForm->brand_name,
                'site_title' => $this->generalForm->site_title,
                'default_locale' => $this->generalForm->default_locale,
                'active_academic_year' => $selectedYear,
            ],
            branding: [
                'primary_color' => $this->brandingForm->primary_color,
                'secondary_color' => $this->brandingForm->secondary_color,
                'accent_color' => $this->brandingForm->accent_color,
                'base_color' => $this->brandingForm->base_color,
                'brand_logo' => $this->brandingForm->brand_logo,
                'site_favicon' => $this->brandingForm->site_favicon,
            ],
            mail: [
                'mail_from_address' => $this->mailSettingsForm->mail_from_address,
                'mail_from_name' => $this->mailSettingsForm->mail_from_name,
                'mail_host' => $this->mailSettingsForm->mail_host,
                'mail_port' => $this->mailSettingsForm->mail_port,
                'mail_encryption' => $this->mailSettingsForm->mail_encryption,
                'mail_username' => $this->mailSettingsForm->mail_username,
                'mail_password' => $this->mailSettingsForm->mail_password,
            ],
        );

        if ($selectedYear) {
            $year = AcademicYear::where('name', $selectedYear)->first();

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

        $sent = $action->execute(
            auth()->user()->email,
            $this->mailSettingsForm->toMailConfig(),
        );

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

    #[Layout('shared::layouts.app')]
    public function render(): View
    {
        return view('settings.system-setting', [
            'presets' => Theme::presets(),
        ]);
    }
}
