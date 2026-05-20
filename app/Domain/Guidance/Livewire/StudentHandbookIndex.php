<?php

declare(strict_types=1);

namespace App\Domain\Guidance\Livewire;

use App\Domain\Guidance\Actions\AcknowledgeHandbookAction;
use App\Domain\Guidance\Models\Handbook;
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

        return view('guidance.student-handbook-index', [
            'handbooks' => $handbooks,
        ]);
    }
}
