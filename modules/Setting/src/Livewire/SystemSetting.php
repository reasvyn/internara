<?php

declare(strict_types=1);

namespace Modules\Setting\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Setting\Services\Contracts\SettingService;

/**
 * Class SystemSetting
 *
 * Provides an interface for administrators to manage essential system-wide settings.
 */
class SystemSetting extends Component
{
    use WithFileUploads;

    /**
     * General settings.
     */
    public string $brand_name = '';

    public string $site_title = '';

    /**
     * Read-only system metadata.
     */
    public string $app_name = '';

    public string $app_version = '';

    /**
     * Operational settings.
     */
    public string $active_academic_year = '';

    public string $attendance_check_in_start = '07:00';

    public string $attendance_late_threshold = '08:00';

    /**
     * Mail settings.
     */
    public string $mail_from_address = '';

    public string $mail_from_name = '';

    public string $mail_host = '';

    public string $mail_port = '';

    public string $mail_encryption = 'tls';

    public string $mail_username = '';

    public string $mail_password = '';

    /**
     * Identity assets.
     */
    public $brand_logo;

    public $site_favicon;

    /**
     * Existing URLs for preview.
     */
    public ?string $current_logo_url = null;

    public ?string $current_favicon_url = null;

    /**
     * Localization settings.
     */
    public string $default_locale = 'id';

    /**
     * Initialize the component with existing values.
     */
    public function mount(SettingService $service): void
    {
        $this->authorize('admin.view');

        // General
        $this->brand_name = $service->getValue('brand_name', 'Internara');
        $this->site_title = $service->getValue('site_title', 'Internara - Internship Management');
        $this->default_locale = $service->getValue('default_locale', 'id');

        // Metadata
        $this->app_name = $service->getValue('app_name', 'Internara');
        $this->app_version = $service->getValue('app_version', 'v0.13.0');

        // Assets
        $this->current_logo_url = $service->getValue('brand_logo');
        $this->current_favicon_url = $service->getValue('site_favicon');

        // Operational
        $this->active_academic_year = $service->getValue(
            'active_academic_year',
            date('Y').'/'.(date('Y') + 1),
        );
        $this->attendance_check_in_start = $service->getValue('attendance_check_in_start', '07:00');
        $this->attendance_late_threshold = $service->getValue('attendance_late_threshold', '08:00');

        // Mail
        $this->mail_from_address = $service->getValue('mail_from_address', '');
        $this->mail_from_name = $service->getValue('mail_from_name', '');
        $this->mail_host = $service->getValue('mail_host', '');
        $this->mail_port = $service->getValue('mail_port', '587');
        $this->mail_encryption = $service->getValue('mail_encryption', 'tls');
        $this->mail_username = $service->getValue('mail_username', '');
        $this->mail_password = $service->getValue('mail_password', '');
    }

    /**
     * Save the system settings.
     */
    public function save(SettingService $service): void
    {
        $this->authorize('admin.update');

        $this->validate([
            // General
            'brand_name' => 'required|string|max:50',
            'site_title' => 'required|string|max:100',
            'default_locale' => 'required|in:id,en',

            // Assets
            'brand_logo' => 'nullable|image|max:1024',
            'site_favicon' => 'nullable|image|max:512',

            // Operational
            'active_academic_year' => 'required|string|regex:/^\d{4}\/\d{4}$/',
            'attendance_check_in_start' => 'required|date_format:H:i',
            'attendance_late_threshold' => 'required|date_format:H:i',

            // Mail
            'mail_from_address' => 'nullable|email',
            'mail_from_name' => 'nullable|string|max:100',
            'mail_host' => 'nullable|string',
            'mail_port' => 'nullable|numeric',
            'mail_encryption' => 'nullable|in:tls,ssl,none',
            'mail_username' => 'nullable|string',
            'mail_password' => 'nullable|string',
        ]);

        $settings = [
            'brand_name' => $this->brand_name,
            'site_title' => $this->site_title,
            'default_locale' => $this->default_locale,
            'active_academic_year' => $this->active_academic_year,
            'attendance_check_in_start' => $this->attendance_check_in_start,
            'attendance_late_threshold' => $this->attendance_late_threshold,
            'mail_from_address' => $this->mail_from_address,
            'mail_from_name' => $this->mail_from_name,
            'mail_host' => $this->mail_host,
            'mail_port' => $this->mail_port,
            'mail_encryption' => $this->mail_encryption,
            'mail_username' => $this->mail_username,
            'mail_password' => $this->mail_password,
        ];

        // Handle File Uploads
        if ($this->brand_logo) {
            $settings['brand_logo'] = $this->brand_logo->store('brand', 'public');
            // Convert to URL if using public disk
            $settings['brand_logo'] = \Illuminate\Support\Facades\Storage::url(
                $settings['brand_logo'],
            );
        }

        if ($this->site_favicon) {
            $settings['site_favicon'] = $this->site_favicon->store('brand', 'public');
            $settings['site_favicon'] = \Illuminate\Support\Facades\Storage::url(
                $settings['site_favicon'],
            );
        }

        $service->setValue($settings);

        flash()->success(__('setting::ui.messages.saved'));

        // Force refresh to update layout branding immediately
        $this->redirect(route('admin.settings'), navigate: true);
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('setting::livewire.system-setting')->layout(
            'ui::components.layouts.dashboard',
            [
                'title' => __('setting::ui.title').' | '.setting('brand_name', setting('app_name')),
            ],
        );
    }
}
