<?php

declare(strict_types=1);

namespace App\Academics\School\Livewire;

use App\Academics\School\Actions\SaveSchoolProfileAction;
use App\Academics\School\Livewire\Forms\SchoolForm;
use App\Core\Livewire\BaseFormView;
use App\Settings\Actions\SetSettingAction;
use App\Settings\Branding\Actions\RemoveBrandAssetAction;
use App\Settings\Branding\Actions\UploadBrandAssetAction;
use App\Settings\Data\SettingData;
use App\Settings\Models\Setting;
use App\Settings\Services\Settings;
use Illuminate\View\View;
use Livewire\WithFileUploads;
use TallStackUi\Traits\Interactions;

class SchoolEditor extends BaseFormView
{
    use Interactions;
    use WithFileUploads;

    public SchoolForm $form;

    public $logo_file = null;

    public function mount(): void
    {
        $this->authorize('update', Setting::class);

        $this->form->loadFromEntity();
    }

    public function updatedLogoFile(
        UploadBrandAssetAction $uploadBrand,
        SetSettingAction $setSetting,
    ): void {
        $this->authorize('update', Setting::class);
        $this->validate(['logo_file' => ['nullable', 'image', 'max:2048']]);

        $url = $uploadBrand->execute($this->logo_file);

        $setSetting->execute(new SettingData(
            key: 'brand_logo',
            value: $url,
            group: 'branding',
        ));

        $this->logo_file = null;
        $this->toast()->success(__('school.logo_saved'))->send();
    }

    public function save(SaveSchoolProfileAction $action): void
    {
        $this->authorize('update', Setting::class);
        $this->validate();

        $this->handleSave(function () use ($action): void {
            $action->execute(data: $this->form->toPayload());
            $this->form->loadFromEntity();
            $this->toast()->success(__('school.save_success'))->send();
            $this->dispatch('saved');
        });
    }

    public function logoPreviewUrl(): ?string
    {
        if ($this->logo_file) {
            try {
                return $this->logo_file->temporaryUrl();
            } catch (\Throwable) {
                return null;
            }
        }

        return $this->getLogoUrl();
    }

    public function confirmAction(RemoveBrandAssetAction $action): void
    {
        $this->authorize('update', Setting::class);

        $action->execute('logo');

        Settings::forget('brand_logo');
        $this->toast()->success(__('school.logo_removed'))->send();
    }

    public function render(): View
    {
        return view('academics.school.school-editor');
    }

    private function getLogoUrl(): ?string
    {
        $setting = Setting::find('brand_logo_ref');

        if (! $setting) {
            return null;
        }

        try {
            return $setting->getFirstMediaUrl('brand_logo', 'thumb') ?: null;
        } catch (\Throwable) {
            return null;
        }
    }
}
