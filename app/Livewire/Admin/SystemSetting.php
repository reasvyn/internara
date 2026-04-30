<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\Audit\LogAuditAction;
use App\Actions\Setting\SetSettingAction;
use App\Support\Settings;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

/**
 * Admin interface for managing system-wide settings.
 *
 * S1 - Secure: Requires admin role; all changes are audit-logged.
 * S2 - Sustain: Centralized admin UI for all configurable system parameters.
 */
class SystemSetting extends Component
{
    use Toast, WithFileUploads;

    /**
     * General settings.
     */
    public string $brand_name = '';

    public string $site_title = '';

    public string $default_locale = 'id';

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

    public string $mail_port = '587';

    public string $mail_encryption = 'tls';

    public string $mail_username = '';

    public string $mail_password = '';

    /**
     * Identity assets.
     */
    public $brand_logo;
    public $site_favicon;
    
    /**
     * Color scheme settings.
     */
    public string $primary_color = '#0ea5e9'; // sky-500
    public string $secondary_color = '#64748b'; // slate-500
    public string $accent_color = '#f59e0b'; // amber-500

    /**
     * Existing URLs for preview.
     */
    public ?string $current_logo_url = null;

    public ?string $current_favicon_url = null;

    /**
     * Initialize the component with existing values.
     */
    public function mount(): void
    {
        // General
        $this->brand_name = Settings::get('brand_name', 'Internara');
        $this->site_title = Settings::get('site_title', 'Internara - Internship Management');
        $this->default_locale = Settings::get('default_locale', 'id');

        // Metadata (resolved via AppInfo)
        $this->app_name = Settings::get('app_name', 'Internara');
        $this->app_version = Settings::get('app_version', '0.0.0');

        // Assets
        $this->current_logo_url = Settings::get('brand_logo');
        $this->current_favicon_url = Settings::get('site_favicon');

        // Color scheme
        $this->primary_color = Settings::get('primary_color', '#0ea5e9');
        $this->secondary_color = Settings::get('secondary_color', '#64748b');
        $this->accent_color = Settings::get('accent_color', '#f59e0b');

        // Operational
        $this->active_academic_year = Settings::get(
            'active_academic_year',
            date('Y').'/'.(date('Y') + 1),
        );
        $this->attendance_check_in_start = Settings::get('attendance_check_in_start', '07:00');
        $this->attendance_late_threshold = Settings::get('attendance_late_threshold', '08:00');

        // Mail
        $this->mail_from_address = Settings::get('mail_from_address', '');
        $this->mail_from_name = Settings::get('mail_from_name', '');
        $this->mail_host = Settings::get('mail_host', '');
        $this->mail_port = Settings::get('mail_port', '587');
        $this->mail_encryption = Settings::get('mail_encryption', 'tls');
        $this->mail_username = Settings::get('mail_username', '');
        $this->mail_password = Settings::get('mail_password', '');
    }

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [
            // General
            'brand_name' => 'required|string|max:50',
            'site_title' => 'required|string|max:100',
            'default_locale' => 'required|in:id,en',

            // Assets
            'brand_logo' => 'nullable|image|max:1024',
            'site_favicon' => 'nullable|image|max:512',

            // Color scheme
            'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],

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
        ];
    }

    /**
     * Save all system settings.
     */
    public function save(SetSettingAction $setSetting, LogAuditAction $logAudit): void
    {
        $validated = $this->validate();

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
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'accent_color' => $this->accent_color,
        ];
        if ($this->brand_logo) {
            $path = $this->brand_logo->store('brand', 'public');
            $settings['brand_logo'] = Storage::url($path);
        }

        if ($this->site_favicon) {
            $path = $this->site_favicon->store('brand', 'public');
            $settings['site_favicon'] = Storage::url($path);
        }

        $setSetting->executeBatch($settings);

        $logAudit->execute(
            action: 'settings_updated',
            subjectType: 'system_settings',
            subjectId: 'global',
            payload: ['keys' => array_keys($settings)],
            module: 'Setting',
        );

        $this->success(__('setting.messages.saved'));

        $this->redirectRoute('admin.settings', navigate: true);
    }

    #[Layout('components.layouts.app', ['title' => 'System Settings'])]
    public function render()
    {
        return view('livewire.admin.system-setting');
    }
}
