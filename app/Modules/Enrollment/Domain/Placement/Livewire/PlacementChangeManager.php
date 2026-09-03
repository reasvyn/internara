<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Placement\Livewire;

use App\Modules\Core\Livewire\BaseRecordManager;
use App\Modules\Enrollment\Domain\Placement\Actions\ApprovePlacementChangeAction;
use App\Modules\Enrollment\Domain\Placement\Actions\RejectPlacementChangeAction;
use App\Modules\Enrollment\Domain\Placement\Models\PlacementChangeRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use TallStackUi\Traits\Interactions;

class PlacementChangeManager extends BaseRecordManager
{
    use AuthorizesRequests;
    use Interactions;

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
