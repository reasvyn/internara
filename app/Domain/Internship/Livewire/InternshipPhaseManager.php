<?php

declare(strict_types=1);

namespace App\Domain\Internship\Livewire;

use App\Domain\Core\Livewire\BaseRecordManager;
use App\Domain\Internship\Actions\CreateInternshipPhaseAction;
use App\Domain\Internship\Actions\DeleteInternshipPhaseAction;
use App\Domain\Internship\Actions\UpdateInternshipPhaseAction;
use App\Domain\Internship\Models\InternshipPhase;
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

    public array $formData = [
        'name' => '',
        'description' => '',
        'start_date' => '',
        'end_date' => '',
        'color' => '',
    ];

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
        $this->formData = [
            'name' => '',
            'description' => '',
            'start_date' => '',
            'end_date' => '',
            'color' => '',
        ];
        $this->confirmTarget = null;
        $this->showModal = true;
    }

    public function edit(InternshipPhase $phase): void
    {
        $this->resetErrorBag();
        $this->formData = [
            'name' => $phase->name,
            'description' => $phase->description ?? '',
            'start_date' => $phase->start_date->format('Y-m-d'),
            'end_date' => $phase->end_date->format('Y-m-d'),
            'color' => $phase->color ?? '',
        ];
        $this->confirmTarget = $phase->id;
        $this->showModal = true;
    }

    public function save(CreateInternshipPhaseAction $create, UpdateInternshipPhaseAction $update): void
    {
        $this->validate([
            'formData.name' => ['required', 'string', 'max:255'],
            'formData.start_date' => ['required', 'date'],
            'formData.end_date' => ['required', 'date', 'after_or_equal:formData.start_date'],
            'formData.color' => ['nullable', 'string', 'max:7'],
        ]);

        if ($this->confirmTarget) {
            $phase = InternshipPhase::findOrFail($this->confirmTarget);
            $update->execute($phase, $this->formData);
            flash()->success('Phase updated.');
        } else {
            $this->formData['internship_id'] = $this->internshipId;
            $maxOrder = InternshipPhase::where('internship_id', $this->internshipId)->max('order');
            $this->formData['order'] = ($maxOrder ?? 0) + 1;
            $create->execute($this->formData);
            flash()->success('Phase created.');
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
        flash()->success('Phase deleted.');

        $this->showConfirm = false;
        $this->confirmTarget = null;
    }

    public function render(): View
    {
        return view('internship.internship-phase-manager');
    }
}
