<?php

declare(strict_types=1);

namespace App\Academics\School\Livewire;

use App\Academics\School\Actions\SaveSchoolProfileAction;
use App\Academics\School\Livewire\Forms\SchoolForm;
use App\Core\Livewire\BaseFormView;
use App\Settings\Models\Setting;
use Illuminate\View\View;
use Livewire\WithFileUploads;

class SchoolEditor extends BaseFormView
{
    use WithFileUploads;

    public SchoolForm $form;

    public $logo_file = null;

    public bool $showConfirm = false;

    public ?string $logoPreviewUrl = null;

    public function mount(): void
    {
        $this->authorize('update', Setting::class);

        $this->form->loadFromEntity();
        $this->logoPreviewUrl = $this->getLogoUrl();
    }

    public function save(SaveSchoolProfileAction $action): void
    {
        $this->authorize('update', Setting::class);
        $this->validate();

        $action->execute(data: $this->form->toPayload(), logoFile: $this->logo_file);

        if ($this->logo_file) {
            $this->logoPreviewUrl = $this->getLogoUrl();
            $this->logo_file = null;
            flash()->success(__('school.logo_saved'));
        }

        $this->form->loadFromEntity();
        flash()->success(__('school.save_success'));
    }

    public function logoPreviewUrl(): ?string
    {
        if ($this->logo_file) {
            return $this->logo_file->temporaryUrl();
        }

        return $this->logoPreviewUrl;
    }

    public function removeLogo(): void
    {
        $this->authorize('update', Setting::class);

        $setting = Setting::find('brand_logo');
        if ($setting) {
            $setting->clearMediaCollection('brand_logo');
        }

        $this->logoPreviewUrl = null;
        $this->showConfirm = false;
        flash()->success(__('school.logo_removed'));
    }

    public function confirmAction(): void
    {
        $this->removeLogo();
    }

    public function render(): View
    {
        return view('academics.school.school-editor');
    }

    private function getLogoUrl(): ?string
    {
        $setting = Setting::find('brand_logo');

        return $setting?->getFirstMediaUrl('brand_logo', 'thumb');
    }
}
