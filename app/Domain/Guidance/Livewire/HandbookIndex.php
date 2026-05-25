<?php

declare(strict_types=1);

namespace App\Domain\Guidance\Livewire;

use App\Domain\Guidance\Actions\AcknowledgeHandbookAction;
use App\Domain\Guidance\Actions\CreateHandbookAction;
use App\Domain\Guidance\Actions\DeleteHandbookAction;
use App\Domain\Guidance\Actions\UpdateHandbookAction;
use App\Domain\Guidance\Livewire\Forms\HandbookForm;
use App\Domain\Guidance\Models\Handbook;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class HandbookIndex extends Component
{
    use AuthorizesRequests, WithPagination;

    public bool $showModal = false;

    public HandbookForm $form;

    public function boot(): void
    {
        $this->authorize('create', Handbook::class);
    }

    public function create(): void
    {
        $this->resetErrorBag();
        $this->form->reset();
        $this->showModal = true;
    }

    public function edit(string $id): void
    {
        $handbook = Handbook::findOrFail($id);

        $this->resetErrorBag();
        $this->form->fill([
            'id' => $handbook->id,
            'title' => $handbook->title,
            'content' => $handbook->content,
            'version' => (string) $handbook->version,
            'is_active' => $handbook->is_active,
        ]);
        $this->showModal = true;
    }

    public function store(CreateHandbookAction $createAction, UpdateHandbookAction $updateAction): void
    {
        $this->form->validate();

        if ($this->form->id) {
            $handbook = Handbook::findOrFail($this->form->id);
            $updateAction->execute($handbook, $this->form->all());
            flash()->success(__('handbook.updated'));
        } else {
            $createAction->execute(auth()->user(), $this->form->all());
            flash()->success(__('handbook.created'));
        }

        $this->showModal = false;
        $this->form->reset();
    }

    public function acknowledge(string $id, AcknowledgeHandbookAction $action): void
    {
        $handbook = Handbook::findOrFail($id);
        $action->execute(auth()->user(), $handbook);
        flash()->success(__('handbook.acknowledged'));
    }

    public function delete(string $id, DeleteHandbookAction $action): void
    {
        $handbook = Handbook::findOrFail($id);
        $action->execute($handbook);
        flash()->success(__('handbook.deleted'));
    }

    public function render(): View
    {
        $handbooks = Handbook::with('author')->latest()->paginate(20);

        return view('guidance.handbook-index', [
            'handbooks' => $handbooks,
        ]);
    }
}
