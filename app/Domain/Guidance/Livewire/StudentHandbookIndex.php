<?php

declare(strict_types=1);

namespace App\Domain\Guidance\Livewire;

use App\Domain\Guidance\Actions\AcknowledgeHandbookAction;
use App\Domain\Guidance\Models\Handbook;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class StudentHandbookIndex extends Component
{
    use AuthorizesRequests;

    public function boot(): void
    {
        $this->authorize('viewAny', Handbook::class);
    }

    public function acknowledge(string $id, AcknowledgeHandbookAction $action): void
    {
        $handbook = Handbook::findOrFail($id);
        $action->execute(auth()->user(), $handbook);
        flash()->success(__('handbook.acknowledged'));
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
