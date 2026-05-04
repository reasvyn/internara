<?php

declare(strict_types=1);

namespace App\Livewire\Internship;

use App\Domain\Internship\Actions\CreatePlacementAction;
use App\Domain\Internship\Actions\DeletePlacementAction;
use App\Domain\Internship\Actions\UpdatePlacementAction;
use App\Domain\Internship\Models\Company;
use App\Domain\Internship\Models\Internship;
use App\Domain\Internship\Models\Placement;
use App\Livewire\Core\BaseRecordManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

/**
 * Modernized Placement Manager using BaseRecordManager pattern.
 */
class PlacementIndex extends BaseRecordManager
{
    public bool $showModal = false;

    public array $formData = [
        'id' => null,
        'company_id' => '',
        'internship_id' => '',
        'name' => '',
        'address' => '',
        'quota' => null,
        'description' => '',
    ];

    /**
     * Define columns and sorting.
     */
    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => __('placement.name'), 'sortable' => true],
            ['key' => 'company.name', 'label' => __('placement.company')],
            ['key' => 'internship.name', 'label' => __('placement.batch')],
            ['key' => 'quota', 'label' => __('placement.quota'), 'class' => 'text-center'],
            ['key' => 'filled_quota', 'label' => __('placement.filled'), 'class' => 'text-center'],
            ['key' => 'actions', 'label' => ''],
        ];
    }

    /**
     * Base query for placements.
     */
    protected function query(): Builder
    {
        return Placement::query()->with(['company', 'internship']);
    }

    /**
     * Search implementation.
     */
    protected function applySearch(Builder $query): Builder
    {
        return $query
            ->where('name', 'like', "%{$this->search}%")
            ->orWhereHas('company', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
    }

    /**
     * Filter implementation.
     */
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
            'available' => Placement::sum(DB::raw('quota - filled_quota')),
        ];
    }

    // --- Record Actions ---

    public function create(): void
    {
        $this->resetErrorBag();
        $this->formData = [
            'id' => null,
            'company_id' => '',
            'internship_id' => '',
            'name' => '',
            'address' => '',
            'quota' => null,
            'description' => '',
        ];
        $this->showModal = true;
    }

    public function edit(Placement $placement): void
    {
        $this->resetErrorBag();
        $this->formData = [
            'id' => $placement->id,
            'company_id' => $placement->company_id,
            'internship_id' => $placement->internship_id,
            'name' => $placement->name,
            'address' => $placement->address ?? '',
            'quota' => $placement->quota,
            'description' => $placement->description ?? '',
        ];
        $this->showModal = true;
    }

    public function save(CreatePlacementAction $create, UpdatePlacementAction $update): void
    {
        $this->validate([
            'formData.company_id' => ['required', 'exists:internship_companies,id'],
            'formData.internship_id' => ['required', 'exists:internships,id'],
            'formData.name' => ['required', 'string', 'max:255'],
            'formData.address' => ['nullable', 'string'],
            'formData.quota' => ['required', 'integer', 'min:1'],
            'formData.description' => ['nullable', 'string'],
        ]);

        if ($this->formData['id']) {
            $placement = Placement::findOrFail($this->formData['id']);
            $update->execute($placement, $this->formData);
            $this->success(__('placement.update_success'));
        } else {
            $create->execute($this->formData);
            $this->success(__('placement.save_success'));
        }

        $this->showModal = false;
    }

    public function delete(
        Placement $placement,
        DeletePlacementAction $deleteAction,
    ): void {
        if ($placement->registrations()->exists()) {
            $this->error(__('placement.delete_blocked'));

            return;
        }

        $deleteAction->execute($placement);
        $this->success(__('placement.delete_success'));
    }

    // --- Bulk Actions ---

    public function deleteSelected(DeletePlacementAction $deleteAction): void
    {
        $this->performBulkAction(__('common.actions.delete'), function ($id) use ($deleteAction) {
            $placement = Placement::find($id);
            if ($placement && ! $placement->registrations()->exists()) {
                $deleteAction->execute($placement);
            }
        });
    }

    #[Layout('layouts::app')]
    public function render()
    {
        return view('livewire.internship.placement-index');
    }
}
