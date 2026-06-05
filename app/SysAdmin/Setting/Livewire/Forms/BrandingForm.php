<?php

declare(strict_types=1);

namespace App\SysAdmin\Setting\Livewire\Forms;

use App\Core\Support\SmartLogger;
use App\Support\Theme;
use Livewire\Form;

class BrandingForm extends Form
{
    public string $primary_color = '';

    public string $secondary_color = '';

    public string $accent_color = '';

    public string $base_color = '';

    public ?string $selected_preset = null;

    public $brand_logo;

    public $site_favicon;

    public ?string $current_logo_url = null;

    public ?string $current_favicon_url = null;

    protected function rules(): array
    {
        return [
            'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'base_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brand_logo' => 'nullable|image|max:1024',
            'site_favicon' => 'nullable|image|max:512',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'primary_color' => __('setting.fields.primary_color'),
            'secondary_color' => __('setting.fields.secondary_color'),
            'accent_color' => __('setting.fields.accent_color'),
            'base_color' => __('setting.fields.base_color'),
            'brand_logo' => __('setting.fields.brand_logo'),
            'site_favicon' => __('setting.fields.site_favicon'),
        ];
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

    /**
     * @return array<int, array{label: string, colors: array<string, string>}>
     */
    public function getPresets(): array
    {
        return Theme::presets();
    }
}
