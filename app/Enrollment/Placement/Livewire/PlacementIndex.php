<?php

declare(strict_types=1);

namespace App\Enrollment\Placement\Livewire;

use App\Core\Exceptions\RejectedException;
use App\Core\Livewire\BaseRecordManager;
use App\Enrollment\Placement\Actions\CreatePlacementAction;
use App\Enrollment\Placement\Actions\DeletePlacementAction;
use App\Enrollment\Placement\Actions\UpdatePlacementAction;
use App\Enrollment\Placement\Livewire\Forms\PlacementForm;
use App\Enrollment\Placement\Models\Placement;
use App\Partners\Company\Models\Company;
use App\Program\Internship\Models\Internship;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use TallStackUi\Traits\Interactions;

class PlacementIndex extends BaseRecordManager
{
    use AuthorizesRequests;
    use Interactions;

    public bool $showModal = false;

    public string $confirmActionType = '';

    public ?string $confirmTarget = null;

    public PlacementForm $form;

    public function boot(): void
    {
        $this->authorize('viewAny', Placement::class);
    }

    public function headers(): array
    {
        return [
            ['index' => 'name', 'label' => __('placement.name'), 'sortable' => true],
            ['index' => 'company.name', 'label' => __('placement.company')],
            ['index' => 'internship.name', 'label' => __('placement.batch')],
            ['index' => 'quota', 'label' => __('placement.quota'), 'class' => 'text-center'],
            ['index' => 'filled_quota', 'label' => __('placement.stats.filled'), 'class' => 'text-center'],
            ['index' => 'actions', 'label' => '', 'sortable' => false],
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
        $this->authorize('create', Placement::class);

        $this->resetErrorBag();
        $this->form->reset();
        $this->showModal = true;
    }

    public function edit(string $id): void
    {
        $placement = Placement::findOrFail($id);
        $this->authorize('update', $placement);

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
            $this->authorize('update', $placement);
            $update->execute($placement, $this->form->all());
            $this->toast()->success(__('placement.update_success'))->send();
        } else {
            $this->authorize('create', Placement::class);
            $create->execute($this->form->all());
            $this->toast()->success(__('placement.save_success'))->send();
        }

        $this->showModal = false;
    }

    public function askDelete(string $id): void
    {
        $this->confirmActionType = 'delete';
        $this->confirmTarget = $id;
        $this->dialog()
            ->question(__('common.actions.confirm_action'), $this->confirmMessage ?? __('common.actions.confirm_message'))
            ->confirm(text: __('common.actions.confirm'), method: 'confirmAction')
            ->cancel(text: __('common.actions.cancel'))
            ->send();
    }

    public function askDeleteSelected(): void
    {
        $this->confirmActionType = 'deleteSelected';
        $this->dialog()
            ->question(__('common.actions.confirm_action'), $this->confirmMessage ?? __('common.actions.confirm_message'))
            ->confirm(text: __('common.actions.confirm'), method: 'confirmAction')
            ->cancel(text: __('common.actions.cancel'))
            ->send();
    }

    public function confirmAction(DeletePlacementAction $deleteAction): void
    {
        try {
            if ($this->confirmActionType === 'delete') {
                $placement = Placement::findOrFail($this->confirmTarget);

                if (! $placement->asPlacementState()->canBeDeleted()) {
                    $this->toast()->error(__('placement.delete_blocked'))->send();

                    return;
                }

                $this->authorize('delete', $placement);
                $deleteAction->execute($placement);
                $this->toast()->success(__('placement.delete_success'))->send();
            } elseif ($this->confirmActionType === 'deleteSelected') {
                $this->performBulkAction(__('common.actions.delete'), function ($id) use ($deleteAction) {
                    $placement = Placement::find($id);
                    if ($placement && $placement->asPlacementState()->canBeDeleted()) {
                        $deleteAction->execute($placement);
                    }
                });
            }
        } catch (RejectedException $e) {
            $this->toast()->error($e->getMessage())->send();
        }
        $this->confirmTarget = null;
        $this->confirmActionType = '';
    }

    #[Layout('ui::layouts.app')]
    public function render(): View
    {
        return view('enrollment.placement.placement-index');
    }
}
