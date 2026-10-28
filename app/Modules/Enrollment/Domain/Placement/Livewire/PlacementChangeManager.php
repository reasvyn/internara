<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Placement\Livewire;

use App\Modules\Enrollment\Domain\Placement\Actions\ApprovePlacementChangeAction;
use App\Modules\Enrollment\Domain\Placement\Actions\RejectPlacementChangeAction;
use App\Modules\Enrollment\Domain\Placement\Actions\RelocateStudentAction;
use App\Modules\Enrollment\Domain\Placement\Data\RelocateStudentData;
use App\Modules\Enrollment\Domain\Placement\Livewire\Forms\PlacementChangeForm;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Placement\Models\PlacementChangeRequest;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\UI\Livewire\BaseRecordManager;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use TallStackUi\Traits\Interactions;

class PlacementChangeManager extends BaseRecordManager
{
    use AuthorizesRequests;
    use Interactions;

    public bool $showRejectModal = false;

    public ?string $rejectingId = null;

    public string $rejectionReason = '';

    public bool $showRelocateModal = false;

    public PlacementChangeForm $form;

    public function boot(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin']), 403);
        $this->authorize('viewAny', PlacementChangeRequest::class);
    }

    public function headers(): array
    {
        return [
            [
                'index' => 'created_at',
                'label' => __('placement_change.requested_at'),
                'sortable' => true,
            ],
            [
                'index' => 'requester.name',
                'label' => __('placement_change.student'),
                'sortable' => true,
            ],
            ['index' => 'fromPlacement.company.name', 'label' => __('placement_change.from_company')],
            ['index' => 'toPlacement.company.name', 'label' => __('placement_change.to_company')],
            ['index' => 'status', 'label' => __('placement_change.status'), 'sortable' => true],
            ['index' => 'reason', 'label' => __('placement_change.reason')],
            ['index' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function applySearch(Builder $query): Builder
    {
        $term = '%'.$this->search.'%';

        return $query->where(function (Builder $q) use ($term) {
            $q->where('reason', 'like', $term)
                ->orWhere('status', 'like', $term)
                ->orWhereHas('requester', fn (Builder $r) => $r->where('name', 'like', $term));
        });
    }

    protected function applyFilters(Builder $query): Builder
    {
        if (($this->filters['status'] ?? '') !== '') {
            $query->where('status', $this->filters['status']);
        }

        return $query;
    }

    protected function query(): Builder
    {
        return PlacementChangeRequest::query()->with([
            'requester',
            'fromPlacement.company',
            'toPlacement.company',
            'registration.mentee.user',
        ]);
    }

    public function openRelocateModal(): void
    {
        $this->authorize('create', PlacementChangeRequest::class);
        $this->resetErrorBag();
        $this->form->reset();
        $this->showRelocateModal = true;
    }

    public function relocate(RelocateStudentAction $action): void
    {
        $this->authorize('create', PlacementChangeRequest::class);
        $this->form->validate();

        $action->execute(new RelocateStudentData(
            registrationId: $this->form->registration_id,
            targetPlacementId: $this->form->to_placement_id,
            reason: $this->form->reason,
            adminId: (string) auth()->id(),
        ));

        $this->toast()->success(__('placement_change.relocate_success'))->send();
        $this->showRelocateModal = false;
        $this->form->reset();
    }

    #[Computed]
    public function activeRegistrations(): Collection
    {
        return Registration::query()
            ->where('status', 'active')
            ->whereNotNull('placement_id')
            ->with(['student', 'placement.company', 'internship'])
            ->get();
    }

    #[Computed]
    public function selectedRegistration(): ?Registration
    {
        if (empty($this->form->registration_id)) {
            return null;
        }

        return Registration::with(['student', 'placement.company', 'internship'])->find($this->form->registration_id);
    }

    #[Computed]
    public function targetPlacements(): Collection
    {
        if (empty($this->form->registration_id)) {
            return collect();
        }

        $reg = Registration::find($this->form->registration_id);
        if (! $reg) {
            return collect();
        }

        return Placement::query()
            ->where('internship_id', $reg->internship_id)
            ->where('id', '!=', $reg->placement_id)
            ->whereHas('company')
            ->with('company')
            ->get()
            ->filter(fn (Placement $p) => $p->asPlacementCapacity()->hasAvailableSlots());
    }

    public function approve(string $id, ApprovePlacementChangeAction $action): void
    {
        $request = PlacementChangeRequest::findOrFail($id);
        $this->authorize('update', $request);
        $action->execute($request);
        $this->toast()->success(__('placement_change.approve_success'))->send();
    }

    public function rejectConfirm(string $id): void
    {
        $this->rejectingId = $id;
        $this->rejectionReason = '';
        $this->showRejectModal = true;
    }

    public function reject(RejectPlacementChangeAction $action): void
    {
        $this->validate(['rejectionReason' => 'required|string|max:2000']);
        $request = PlacementChangeRequest::findOrFail($this->rejectingId);
        $this->authorize('update', $request);
        $action->execute($request, $this->rejectionReason);
        $this->toast()->success(__('placement_change.reject_success'))->send();
        $this->showRejectModal = false;
        $this->rejectingId = null;
    }

    #[Layout('ui::layouts.app')]
    public function render(): View
    {
        return view('enrollment.placement.placement-change-manager');
    }
}
