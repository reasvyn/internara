<?php

declare(strict_types=1);

namespace App\Livewire\Internship;

use App\Actions\Internship\ApprovePlacementChangeAction;
use App\Actions\Internship\RejectPlacementChangeAction;
use App\Livewire\Core\BaseRecordManager;
use App\Models\PlacementChangeRequest;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;

class PlacementChangeManager extends BaseRecordManager
{
    public bool $showRejectModal = false;

    public ?string $rejectingId = null;

    public string $rejectionReason = '';

    public function headers(): array
    {
        return [
            ['key' => 'created_at', 'label' => __('placement_change.requested_at'), 'sortable' => true],
            ['key' => 'student_name', 'label' => __('placement_change.student'), 'sortable' => true],
            ['key' => 'from_company', 'label' => __('placement_change.from_company')],
            ['key' => 'to_company', 'label' => __('placement_change.to_company')],
            ['key' => 'status', 'label' => __('placement_change.status'), 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return PlacementChangeRequest::query()
            ->select([
                'placement_change_requests.*',
                'students.name as student_name',
                'from_company.name as from_company',
                'to_company.name as to_company',
            ])
            ->join('internship_registrations', 'placement_change_requests.registration_id', '=', 'internship_registrations.id')
            ->join('users as students', 'placement_change_requests.requested_by', '=', 'students.id')
            ->join('internship_placements as from_p', 'placement_change_requests.from_placement_id', '=', 'from_p.id')
            ->join('internship_companies as from_company', 'from_p.company_id', '=', 'from_company.id')
            ->leftJoin('internship_placements as to_p', 'placement_change_requests.to_placement_id', '=', 'to_p.id')
            ->leftJoin('internship_companies as to_company', 'to_p.company_id', '=', 'to_company.id');
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

    #[Layout('layouts::app')]
    public function render()
    {
        return view('livewire.internship.placement-change-manager');
    }
}
