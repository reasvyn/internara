<?php

declare(strict_types=1);

namespace Modules\Guidance\Livewire;

use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Guidance\Services\Contracts\HandbookService;

class HandbookHub extends Component
{
    /**
     * List of active handbooks.
     */
    public Collection $handbooks;

    /**
     * Initialize the component.
     */
    public function mount(HandbookService $service): void
    {
        $this->handbooks = $service->get(['is_active' => true]);
    }

    /**
     * Acknowledge a handbook.
     */
    public function acknowledge(string $handbookId, HandbookService $service): void
    {
        $service->acknowledge(auth()->id() ?: '', $handbookId);

        // Refresh handbooks list to update UI state
        $this->handbooks = $service->get(['is_active' => true]);

        $this->dispatch('handbook-acknowledged');
    }

    /**
     * Render the component view.
     */
    public function render(): View
    {
        return view('guidance::livewire.handbook-hub');
    }
}
