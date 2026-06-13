<?php

declare(strict_types=1);

namespace App\SysAdmin\Livewire;

use App\Core\Exceptions\RejectedException;
use App\Enrollment\AccountApplication\Actions\ApproveAccountApplicationAction;
use App\Enrollment\AccountApplication\Actions\RejectAccountApplicationAction;
use App\Enrollment\AccountApplication\Enums\AccountApplicationStatus;
use App\Enrollment\AccountApplication\Models\AccountApplication;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ApplicationReview extends Component
{
    public ?string $rejectId = null;

    public string $rejectionReason = '';

    public bool $showRejectModal = false;

    #[Computed]
    public function pendingApplications(): Collection
    {
        return AccountApplication::with(['internship', 'school'])
            ->where('status', AccountApplicationStatus::PENDING->value)
            ->latest()
            ->get();
    }

    public function approve(string $id, ApproveAccountApplicationAction $action): void
    {
        try {
            $action->execute($id, auth()->user());
            flash()->success(__('internship.applications.success_approved'));
        } catch (RejectedException $e) {
            flash()->error($e->getMessage());
        }
    }

    public function confirmReject(string $id): void
    {
        $this->rejectId = $id;
        $this->rejectionReason = '';
        $this->showRejectModal = true;
    }

    public function reject(RejectAccountApplicationAction $action): void
    {
        $this->validate(['rejectionReason' => 'required|string|max:1000']);

        try {
            $action->execute($this->rejectId, auth()->user(), $this->rejectionReason);
            flash()->success(__('internship.applications.success_rejected'));
        } catch (RejectedException $e) {
            flash()->error($e->getMessage());
        }

        $this->showRejectModal = false;
        $this->rejectId = null;
        $this->rejectionReason = '';
    }

    public function render(): View
    {
        return view('sysadmin.livewire.application-review');
    }
}
