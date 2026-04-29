<?php

declare(strict_types=1);

namespace Modules\Guidance\Livewire;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Guidance\Services\Contracts\HandbookService;

class HandbookHub extends Component
{
    /**
     * Get the list of active handbooks with their acknowledgment status.
     */
    #[Computed]
    public function handbooks(): Collection
    {
        $service = app(HandbookService::class);
        $userId = Auth::id() ?: '';

        return $service
            ->get(['is_active' => true])
            ->map(function ($handbook) use ($service, $userId) {
                $handbook->is_acknowledged = $service->hasAcknowledged($userId, $handbook->id);

                return $handbook;
            });
    }

    /**
     * Acknowledge a handbook.
     */
    public function acknowledge(string $handbookId, HandbookService $service): void
    {
        $service->acknowledge(Auth::id() ?: '', $handbookId);

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
