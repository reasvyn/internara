<?php

declare(strict_types=1);

namespace App\Modules\Setting\Livewire\Forms;

use Livewire\Form;

class GeneralSettingsForm extends Form
{
    public string $brand_name = '';

    public string $site_title = '';

    public string $default_locale = 'id';

    public string $default_theme = 'system';

    public string $active_academic_year = '';

    public string $support_email = '';

    protected function rules(): array
    {
        return [
            'brand_name' => 'required|string|max:50',
            'site_title' => 'required|string|max:100',
            'default_locale' => 'required|in:id,en',
            'default_theme' => 'required|in:system,light,dark',
            'active_academic_year' => 'required|string|regex:/^\d{4}\/\d{4}$/',
            'support_email' => 'nullable|email|max:100',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'brand_name' => __('setting.fields.brand_name'),
            'site_title' => __('setting.fields.site_title'),
            'default_locale' => __('setting.fields.default_locale'),
            'default_theme' => __('setting.fields.default_theme'),
            'active_academic_year' => __('setting.fields.active_academic_year'),
            'support_email' => __('setting.fields.support_email'),
        ];
    }
}
