<?php

declare(strict_types=1);

namespace App\Modules\SysAdmin\Livewire;

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\AccountApplication\Actions\ApproveAccountApplicationAction;
use App\Modules\Enrollment\Domain\AccountApplication\Actions\RejectAccountApplicationAction;
use App\Modules\Enrollment\Domain\AccountApplication\Data\RejectAccountApplicationData;
use App\Modules\Enrollment\Domain\AccountApplication\Enums\AccountApplicationStatus;
use App\Modules\Enrollment\Domain\AccountApplication\Models\AccountApplication;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

class ApplicationReview extends Component
{
    use Interactions;

    public ?string $rejectId = null;

    public string $rejectionReason = '';

    public bool $showRejectModal = false;

    #[Computed]
    public function pendingApplications(): Collection
    {
        return AccountApplication::with(['department'])
            ->where('status', AccountApplicationStatus::PENDING->value)
            ->latest()
            ->get();
    }

    public function approve(string $id, ApproveAccountApplicationAction $action): void
    {
        $this->authorize('update', AccountApplication::findOrFail($id));

        try {
            $action->execute($id, auth()->user());
            $this->toast()->success(__('internship.applications.success_approved'))->send();
        } catch (RejectedException $e) {
            $this->toast()->error($e->getMessage())->send();
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

        $this->authorize('update', AccountApplication::findOrFail($this->rejectId));

        try {
            $action->execute(new RejectAccountApplicationData(
                applicationId: $this->rejectId,
                reason: $this->rejectionReason,
            ));
            $this->toast()->success(__('internship.applications.success_rejected'))->send();
        } catch (RejectedException $e) {
            $this->toast()->error($e->getMessage())->send();
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
