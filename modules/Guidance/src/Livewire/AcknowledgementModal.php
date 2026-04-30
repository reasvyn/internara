<?php

declare(strict_types=1);

namespace Modules\Guidance\Livewire;

use Livewire\Component;
use Modules\Guidance\Services\Contracts\HandbookService;

class AcknowledgementModal extends Component
{
    /**
     * Whether the student has completed all mandatory acknowledgements.
     */
    public bool $isComplete = false;

    /**
     * Initialize the component.
     */
    public function mount(HandbookService $service): void
    {
        $this->isComplete = $service->hasCompletedMandatory(auth()->id() ?: '');
    }

    /**
     * Listen for handbook acknowledgement events.
     */
    protected $listeners = ['handbook-acknowledged' => 'checkCompletion'];

    /**
     * Refresh completion status.
     */
    public function checkCompletion(HandbookService $service): void
    {
        $this->isComplete = $service->hasCompletedMandatory(auth()->id() ?: '');
    }

    /**
     * Render the component view.
     */
    public function render()
    {
        return view('guidance::livewire.acknowledgement-modal');
    }
}
