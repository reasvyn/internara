<?php

declare(strict_types=1);

namespace App\Livewire\Guidance;

use App\Actions\Guidance\AcknowledgeHandbookAction;
use App\Models\Handbook;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StudentHandbookIndex extends Component
{
    public function acknowledge(Handbook $handbook, AcknowledgeHandbookAction $action): void
    {
        $action->execute(auth()->user(), $handbook);
        flash()->success('Handbook acknowledged.');
    }

    public function render(): View
    {
        $handbooks = Handbook::with(['acknowledgements' => fn ($q) => $q->where('user_id', auth()->id())])
            ->where('is_active', true)
            ->latest()
            ->get();

        return view('livewire.guidance.student-handbook-index', [
            'handbooks' => $handbooks,
        ]);
    }
}
