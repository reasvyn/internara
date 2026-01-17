<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Internship\Livewire\Forms\PlacementForm;
use Modules\Internship\Services\Contracts\InternshipPlacementService;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\Shared\Livewire\Concerns\ManagesRecords;

class PlacementManager extends Component
{
    use ManagesRecords;

    public PlacementForm $form;

    /**
     * Initialize the component.
     */
    public function boot(InternshipPlacementService $placementService): void
    {
        $this->service = $placementService;
    }

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->authorize('internship.update');
    }

    /**
     * Get internships for the dropdown.
     */
    public function getInternshipsProperty(): \Illuminate\Support\Collection
    {
        return app(InternshipService::class)->all(['id', 'title']);
    }

    public function render(): View
    {
        return view('internship::livewire.placement-manager');
    }
}
