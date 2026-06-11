<?php

declare(strict_types=1);

namespace App\Enrollment\Placement\Livewire;

use App\Core\Livewire\BaseRecordManager;
use App\Enrollment\Placement\Actions\ApprovePlacementChangeAction;
use App\Enrollment\Placement\Actions\RejectPlacementChangeAction;
use App\Enrollment\Placement\Models\PlacementChangeRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;

class PlacementChangeManager extends BaseRecordManager
{
    use AuthorizesRequests;

    public bool $showRejectModal = false;

    public ?string $rejectingId = null;

    public string $rejectionReason = '';

    public function boot(): void
    {
        $this->authorize('viewAny', PlacementChangeRequest::class);
    }

    public function headers(): array
    {
        return [
            [
                'key' => 'created_at',
                'label' => __('placement_change.requested_at'),
                'sortable' => true,
            ],
            [
                'key' => 'requester.name',
                'label' => __('placement_change.student'),
                'sortable' => true,
            ],
            ['key' => 'fromPlacement.company.name', 'label' => __('placement_change.from_company')],
            ['key' => 'toPlacement.company.name', 'label' => __('placement_change.to_company')],
            ['key' => 'status', 'label' => __('placement_change.status'), 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
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

    public function approve(string $id, ApprovePlacementChangeAction $action): void
    {
        $request = PlacementChangeRequest::findOrFail($id);
        $action->execute($request);
        flash()->success(__('placement_change.approve_success'));
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
        $action->execute($request, $this->rejectionReason);
        flash()->success(__('placement_change.reject_success'));
        $this->showRejectModal = false;
        $this->rejectingId = null;
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        return view('enrollment.placement.placement-change-manager');
    }
}
