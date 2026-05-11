<?php

declare(strict_types=1);

namespace App\Livewire\Guidance;

use App\Actions\Guidance\AcknowledgeHandbookAction;
use App\Actions\Guidance\CreateHandbookAction;
use App\Models\Handbook;
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
        $this->dispatch('notify', type: 'success', message: 'Handbook created successfully.');
    }

    public function acknowledge(Handbook $handbook, AcknowledgeHandbookAction $action): void
    {
        $action->execute(auth()->user(), $handbook);
        $this->dispatch('notify', type: 'success', message: 'Handbook acknowledged.');
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Handbook::class);

        $handbooks = Handbook::with('author')->latest()->paginate(20);

        return view('livewire.admin.handbooks.index', [
            'handbooks' => $handbooks,
        ]);
    }
}
