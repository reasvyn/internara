<?php

declare(strict_types=1);

namespace App\Enrollment\Placement\Livewire;

use App\Core\Livewire\BaseRecordManager;
use App\Enrollment\Placement;
use App\Enrollment\Placement\Actions\CreatePlacementAction;
use App\Enrollment\Placement\Actions\DeletePlacementAction;
use App\Enrollment\Placement\Actions\UpdatePlacementAction;
use App\Enrollment\Placement\Livewire\Forms\PlacementForm;
use App\Partners\Company\Models\Company;
use App\Program\Internship\Models\Internship;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

class PlacementIndex extends BaseRecordManager
{
    use AuthorizesRequests;

    public bool $showModal = false;

    public PlacementForm $form;

    public function boot(): void
    {
        $this->authorize('viewAny', Placement::class);
    }

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => __('placement.name'), 'sortable' => true],
            ['key' => 'company.name', 'label' => __('placement.company')],
            ['key' => 'internship.name', 'label' => __('placement.batch')],
            ['key' => 'quota', 'label' => __('placement.quota'), 'class' => 'text-center'],
            ['key' => 'filled_quota', 'label' => __('placement.filled'), 'class' => 'text-center'],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return Placement::query()->with(['company', 'internship']);
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query
            ->where('name', 'like', "%{$this->search}%")
            ->orWhereHas('company', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
    }

    protected function applyFilters(Builder $query): Builder
    {
        return $query
            ->when($this->filters['company_id'] ?? null, function ($q, $companyId) {
                $q->where('company_id', $companyId);
            })
            ->when($this->filters['internship_id'] ?? null, function ($q, $internshipId) {
                $q->where('internship_id', $internshipId);
            });
    }

    #[Computed]
    public function companies()
    {
        return Company::orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function internships()
    {
        return Internship::whereIn('status', ['published', 'active'])
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'total' => Placement::count(),
            'total_quota' => Placement::sum('quota'),
            'filled' => Placement::sum('filled_quota'),
            'available' => Placement::get()->sum(fn ($p) => $p->availableSlots()),
        ];
    }

    // --- Record Actions ---

    public function create(): void
    {
        $this->resetErrorBag();
        $this->form->reset();
        $this->showModal = true;
    }

    public function edit(string $id): void
    {
        $placement = Placement::findOrFail($id);

        $this->resetErrorBag();
        $this->form->fill([
            'id' => $placement->id,
            'company_id' => $placement->company_id,
            'internship_id' => $placement->internship_id,
            'name' => $placement->name,
            'address' => $placement->address ?? '',
            'quota' => $placement->quota,
            'description' => $placement->description ?? '',
        ]);
        $this->showModal = true;
    }

    public function save(CreatePlacementAction $create, UpdatePlacementAction $update): void
    {
        $this->form->validate();

        if ($this->form->id) {
            $placement = Placement::findOrFail($this->form->id);
            $update->execute($placement, $this->form->all());
            flash()->success(__('placement.update_success'));
        } else {
            $create->execute($this->form->all());
            flash()->success(__('placement.save_success'));
        }

        $this->showModal = false;
    }

    public function delete(string $id, DeletePlacementAction $deleteAction): void
    {
        $placement = Placement::findOrFail($id);

        if (! $placement->asPlacementState()->canBeDeleted()) {
            flash()->error(__('placement.delete_blocked'));

            return;
        }

        $deleteAction->execute($placement);
        flash()->success(__('placement.delete_success'));
    }

    // --- Bulk Actions ---

    public function deleteSelected(DeletePlacementAction $deleteAction): void
    {
        $this->performBulkAction(__('common.actions.delete'), function ($id) use ($deleteAction) {
            $placement = Placement::find($id);
            if ($placement && $placement->asPlacementState()->canBeDeleted()) {
                $deleteAction->execute($placement);
            }
        });
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        return view('enrollment.placement.placement-index');
    }
}
