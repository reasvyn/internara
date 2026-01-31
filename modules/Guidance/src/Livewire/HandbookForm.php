<?php

declare(strict_types=1);

namespace Modules\Guidance\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Guidance\Services\Contracts\HandbookService;

/**
 * Class HandbookForm
 *
 * Handles the creation and modification of handbook records and their associated files.
 */
class HandbookForm extends Component
{
    use WithFileUploads;

    /**
     * The handbook ID being edited, if any.
     */
    public ?string $handbookId = null;

    /**
     * Form data.
     */
    public array $data = [
        'title' => '',
        'description' => '',
        'version' => '1.0',
        'is_active' => true,
        'is_mandatory' => true,
    ];

    /**
     * The uploaded PDF file.
     */
    public $file;

    /**
     * Initialize the component.
     */
    public function mount(?string $handbookId, HandbookService $service): void
    {
        $this->handbookId = $handbookId;

        if ($this->handbookId) {
            $handbook = $service->find($this->handbookId);
            if ($handbook) {
                $this->data = [
                    'title' => $handbook->title,
                    'description' => $handbook->description ?? '',
                    'version' => $handbook->version,
                    'is_active' => $handbook->is_active,
                    'is_mandatory' => $handbook->is_mandatory,
                ];
            }
        }
    }

    /**
     * Validation rules.
     */
    protected function rules(): array
    {
        return [
            'data.title' => 'required|string|max:255',
            'data.description' => 'nullable|string',
            'data.version' => 'required|string|max:20',
            'data.is_active' => 'boolean',
            'data.is_mandatory' => 'boolean',
            'file' => $this->handbookId
                ? 'nullable|mimes:pdf|max:10240'
                : 'required|mimes:pdf|max:10240',
        ];
    }

    /**
     * Save the handbook.
     */
    public function save(HandbookService $service): void
    {
        $this->validate();

        if ($this->handbookId) {
            $handbook = $service->update($this->handbookId, $this->data);
        } else {
            $handbook = $service->create($this->data);
        }

        if ($this->file) {
            $handbook->setMedia($this->file, 'document');
        }

        $this->dispatch('handbookSaved');
    }

    /**
     * Render the component view.
     */
    public function render(): View
    {
        return view('guidance::livewire.handbook-form');
    }
}
