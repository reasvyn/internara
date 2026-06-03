<?php

declare(strict_types=1);

namespace App\Domain\Program\Aggregates\InternshipPhase\Livewire;

use App\Domain\Core\Livewire\BaseRecordManager;
use App\Domain\Program\Aggregates\InternshipPhase\Actions\CreateInternshipPhaseAction;
use App\Domain\Program\Aggregates\InternshipPhase\Actions\DeleteInternshipPhaseAction;
use App\Domain\Program\Aggregates\InternshipPhase\Actions\UpdateInternshipPhaseAction;
use App\Domain\Program\Aggregates\InternshipPhase\Livewire\Forms\InternshipPhaseForm;
use App\Domain\Program\Aggregates\Internship\Models\InternshipPhase;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class InternshipPhaseManager extends BaseRecordManager
{
    public bool $showModal = false;

    public bool $showConfirm = false;

    public string $confirmMessage = '';

    public ?string $confirmTarget = null;

    #[Url(as: 'internship', history: true)]
    public ?string $internshipId = null;

    public InternshipPhaseForm $form;

    public function boot(): void
    {
        $this->authorize('viewAny', InternshipPhase::class);
    }

    public function headers(): array
    {
        return [
            ['key' => 'order', 'label' => 'Order', 'sortable' => true],
            ['key' => 'name', 'label' => 'Phase', 'sortable' => true],
            ['key' => 'start_date', 'label' => 'Start', 'sortable' => true],
            ['key' => 'end_date', 'label' => 'End', 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return InternshipPhase::query()
            ->when($this->internshipId, fn ($q) => $q->where('internship_id', $this->internshipId))
            ->orderBy('order');
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where('name', 'like', "%{$this->search}%");
    }

    public function create(): void
    {
        $this->resetErrorBag();
        $this->form->reset();
        $this->confirmTarget = null;
        $this->showModal = true;
    }

    public function edit(string $id): void
    {
        $phase = InternshipPhase::findOrFail($id);

        $this->resetErrorBag();
        $this->form->fill([
            'name' => $phase->name,
            'description' => $phase->description ?? '',
            'start_date' => $phase->start_date->format('Y-m-d'),
            'end_date' => $phase->end_date->format('Y-m-d'),
            'color' => $phase->color ?? '',
        ]);
        $this->confirmTarget = $phase->id;
        $this->showModal = true;
    }

    public function save(CreateInternshipPhaseAction $create, UpdateInternshipPhaseAction $update): void
    {
        $this->form->validate();

        if ($this->confirmTarget) {
            $phase = InternshipPhase::findOrFail($this->confirmTarget);
            $update->execute($phase, $this->form->all());
            flash()->success(__('internship.phase_updated'));
        } else {
            $data = $this->form->all();
            $data['internship_id'] = $this->internshipId;
            $maxOrder = InternshipPhase::where('internship_id', $this->internshipId)->max('order');
            $data['order'] = ($maxOrder ?? 0) + 1;
            $create->execute($data);
            flash()->success(__('internship.phase_created'));
        }

        $this->showModal = false;
        $this->confirmTarget = null;
    }

    public function askDelete(string $id): void
    {
        $phase = InternshipPhase::findOrFail($id);

        $this->confirmTarget = $id;
        $this->confirmMessage = __('Delete phase ":name"?', ['name' => $phase->name]);
        $this->showConfirm = true;
    }

    public function confirmAction(DeleteInternshipPhaseAction $deleteAction): void
    {
        if ($this->confirmTarget === null) {
            return;
        }

        $phase = InternshipPhase::findOrFail($this->confirmTarget);
        $deleteAction->execute($phase);
        flash()->success(__('internship.phase_deleted'));

        $this->showConfirm = false;
        $this->confirmTarget = null;
    }

    public function render(): View
    {
        return view('internship.internship-phase-manager');
    }
}
