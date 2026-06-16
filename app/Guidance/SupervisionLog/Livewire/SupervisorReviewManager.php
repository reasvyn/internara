<?php

declare(strict_types=1);

namespace App\Guidance\SupervisionLog\Livewire;

use App\Core\Exceptions\RejectedException;
use App\Core\Livewire\BaseRecordManager;
use App\Guidance\SupervisionLog\Actions\ReviewLogAction;
use App\Guidance\SupervisionLog\Models\SupervisionLog;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;

class SupervisorReviewManager extends BaseRecordManager
{
    public bool $showReviewModal = false;

    public ?string $reviewTarget = null;

    public string $feedback = '';

    public function headers(): array
    {
        return [
            ['key' => 'registration.student.name', 'label' => __('guidance.student'), 'sortable' => true],
            ['key' => 'date', 'label' => __('guidance.date'), 'sortable' => true],
            ['key' => 'topic', 'label' => __('guidance.topic')],
            ['key' => 'status', 'label' => __('guidance.status')],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return SupervisionLog::query()
            ->where('supervisor_id', auth()->id())
            ->with(['registration.student'])
            ->latest('date');
    }

    public function askReview(string $id): void
    {
        $this->reviewTarget = $id;
        $this->feedback = '';
        $this->showReviewModal = true;
    }

    public function confirmReview(ReviewLogAction $action): void
    {
        if ($this->reviewTarget === null) {
            return;
        }

        $this->validate(['feedback' => 'required|string']);

        try {
            $log = SupervisionLog::findOrFail($this->reviewTarget);
            $this->authorize('review', $log);
            $action->execute($log, auth()->user(), $this->feedback);
            flash()->success(__('guidance.log_reviewed'));
        } catch (RejectedException $e) {
            flash()->error($e->getMessage());
        }

        $this->showReviewModal = false;
        $this->reviewTarget = null;
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        return view('guidance.supervision-log.supervisor-review-manager');
    }
}
