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
    public string $app_version = '';

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

        $this->brand_name = $service->getValue('brand_name', 'Internara');
        $this->site_title = $service->getValue('site_title', 'Internara - Internship Management');
        $this->app_version = $service->getValue('app_version', 'v0.13.0');
        $this->default_locale = $service->getValue('default_locale', 'id');

        $this->current_logo_url = $service->getValue('brand_logo');
        $this->current_favicon_url = $service->getValue('site_favicon');
    }

    /**
     * Save the system settings.
     */
    public function save(SettingService $service): void
    {
        $this->authorize('admin.update');

        $this->validate([
            'brand_name' => 'required|string|max:50',
            'site_title' => 'required|string|max:100',
            'app_version' => 'required|string|max:20',
            'brand_logo' => 'nullable|image|max:1024',
            'site_favicon' => 'nullable|image|max:512',
            'default_locale' => 'required|in:id,en',
        ]);

        $settings = [
            'brand_name' => $this->brand_name,
            'site_title' => $this->site_title,
            'app_version' => $this->app_version,
            'default_locale' => $this->default_locale,
        ];

        // Handle File Uploads
        if ($this->brand_logo) {
            $settings['brand_logo'] = $this->brand_logo->store('brand', 'public');
            // Convert to URL if using public disk
            $settings['brand_logo'] = \Illuminate\Support\Facades\Storage::url($settings['brand_logo']);
        }

        if ($this->site_favicon) {
            $settings['site_favicon'] = $this->site_favicon->store('brand', 'public');
            $settings['site_favicon'] = \Illuminate\Support\Facades\Storage::url($settings['site_favicon']);
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
        return view('setting::livewire.system-setting')
            ->layout('ui::components.layouts.dashboard', [
                'title' => __('setting::ui.title') . ' | ' . setting('brand_name', setting('app_name')),
            ]);
    }
}
