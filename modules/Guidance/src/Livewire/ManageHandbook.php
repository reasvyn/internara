<?php

declare(strict_types=1);

namespace Modules\Guidance\Livewire;

use Illuminate\View\View;
use Modules\Guidance\Livewire\Forms\HandbookForm;
use Modules\Guidance\Models\Handbook;
use Modules\Guidance\Services\Contracts\HandbookService;
use Modules\UI\Livewire\RecordManager;

/**
 * Class ManageHandbook
 *
 * Provides the administrative interface for managing instructional handbooks.
 */
class ManageHandbook extends RecordManager
{
    public HandbookForm $form;

    /**
     * Initialize the component metadata and services.
     */
    public function boot(HandbookService $handbookService): void
    {
        $this->service = $handbookService;
        $this->eventPrefix = 'handbook';
        $this->modelClass = Handbook::class;
    }

    /**
     * Configure the component's basic properties.
     */
    public function initialize(): void
    {
        $this->title = __('guidance::ui.manage_title');
        $this->subtitle = __('guidance::ui.manage_subtitle');
        $this->context = 'guidance::ui.hub_title';
        $this->addLabel = __('guidance::ui.add_handbook');
        $this->deleteConfirmMessage = __('guidance::ui.delete_confirm');

        $this->viewPermission = 'internship.manage';
        $this->createPermission = 'internship.manage';
        $this->updatePermission = 'internship.manage';
        $this->deletePermission = 'internship.manage';

        $this->searchable = ['title', 'description', 'version'];
        $this->sortable = ['title', 'version', 'is_mandatory', 'is_active', 'created_at'];
    }

    /**
     * Define the table structure.
     */
    protected function getTableHeaders(): array
    {
        return [
            ['key' => 'title', 'label' => __('guidance::ui.handbook_title'), 'sortable' => true],
            ['key' => 'version', 'label' => __('guidance::ui.version_label'), 'sortable' => true],
            ['key' => 'is_mandatory', 'label' => __('guidance::ui.mandatory'), 'sortable' => true],
            ['key' => 'is_active', 'label' => __('guidance::ui.status'), 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'class' => 'w-1'],
        ];
    }

    /**
     * Save the handbook with media handling.
     */
    public function save(): void
    {
        $this->form->validate([
            'form.title' => 'required|string|max:255',
            'form.version' => 'required|string|max:20',
            'form.file' => $this->form->id
                ? 'nullable|mimes:pdf|max:10240'
                : 'required|mimes:pdf|max:10240',
        ]);

        try {
            $data = $this->form->all();
            unset($data['file']);

            if ($this->form->id) {
                $handbook = $this->service->update($this->form->id, $data);
            } else {
                $handbook = $this->service->create($data);
            }

            if ($this->form->file) {
                $handbook->setMedia($this->form->file, 'document');
            }

            $this->toggleModal(self::MODAL_FORM, false);
            flash()->success(__('guidance::messages.handbook_saved'));
            $this->dispatch('handbook:saved');
        } catch (\Throwable $e) {
            $this->handleAppExceptionInLivewire($e);
        }
    }

    /**
     * Render the component view.
     */
    public function render(): View
    {
        return view('guidance::livewire.manage-handbook');
    }
}
