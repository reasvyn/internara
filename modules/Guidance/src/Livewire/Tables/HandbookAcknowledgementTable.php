<?php

declare(strict_types=1);

namespace Modules\Guidance\Livewire\Tables;

use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Guidance\Services\Contracts\HandbookService;
use Modules\Internship\Services\Contracts\RegistrationService;

/**
 * Class HandbookAcknowledgementTable
 *
 * Provides a report of students who have or haven't acknowledged mandatory handbooks.
 */
class HandbookAcknowledgementTable extends Component
{
    use WithPagination;

    /**
     * Filter by handbook.
     */
    public ?string $handbookId = null;

    /**
     * Initialize the component.
     */
    public function mount(?string $handbookId = null): void
    {
        $this->handbookId = $handbookId;
    }

    /**
     * Render the component view.
     */
    public function render(RegistrationService $regService, HandbookService $handbookService): View
    {
        // Get active registrations
        $registrations = $regService
            ->query(['latest_status' => 'active'])
            ->with(['student'])
            ->paginate(15);

        // Fetch handbooks for reference
        $handbooks = $handbookService->get(['is_active' => true, 'is_mandatory' => true]);

        return view('guidance::livewire.tables.handbook-acknowledgement-table', [
            'registrations' => $registrations,
            'handbooks' => $handbooks,
        ]);
    }
}
