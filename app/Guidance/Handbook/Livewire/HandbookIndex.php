<?php

declare(strict_types=1);

namespace App\Guidance\Handbook\Livewire;

use App\Guidance\Handbook\Models\Handbook;
use App\Guidance\HandbookAcknowledgement\Actions\AcknowledgeHandbookAction;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class HandbookIndex extends Component
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
        $user = auth()->user();

        $audience = match (true) {
            $user->hasRole('student') => ['all', 'student'],
            $user->hasRole('teacher') => ['all', 'teacher'],
            $user->hasRole('supervisor') => ['all', 'supervisor'],
            default => ['all'],
        };

        $handbooks = Handbook::with(['acknowledgements' => fn ($q) => $q->where('user_id', $user->id)])
            ->where('is_active', true)
            ->whereIn('target_audience', $audience)
            ->latest()
            ->get();

        return view('guidance.handbook.handbook-index', [
            'handbooks' => $handbooks,
        ]);
    }
}
