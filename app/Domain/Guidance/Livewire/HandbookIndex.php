<?php

declare(strict_types=1);

namespace App\Domain\Guidance\Livewire;

use App\Domain\Guidance\Actions\AcknowledgeHandbookAction;
use App\Domain\Guidance\Actions\CreateHandbookAction;
use App\Domain\Guidance\Models\Handbook;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class HandbookIndex extends Component
{
    use WithPagination;

    public bool $showModal = false;

    public string $title = '';

    public string $content = '';

    public string $version = '1';

    public function resetForm(): void
    {
        $this->title = '';
        $this->content = '';
        $this->version = '1';
        $this->resetErrorBag();
    }

    public function store(CreateHandbookAction $action): void
    {
        Gate::authorize('create', Handbook::class);

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'version' => ['required', 'integer', 'min:1'],
        ]);

        $action->execute(auth()->user(), [
            'title' => $this->title,
            'content' => $this->content,
            'version' => $this->version,
        ]);

        $this->showModal = false;
        $this->resetForm();
        flash()->success('Handbook created successfully.');
    }

    public function acknowledge(Handbook $handbook, AcknowledgeHandbookAction $action): void
    {
        $action->execute(auth()->user(), $handbook);
        flash()->success('Handbook acknowledged.');
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Handbook::class);

        $handbooks = Handbook::with('author')->latest()->paginate(20);

        return view('guidance.handbook-index', [
            'handbooks' => $handbooks,
        ]);
    }
}
