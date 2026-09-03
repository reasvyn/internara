<?php

declare(strict_types=1);

namespace App\Modules\Journals\Domain\SupervisionLog\Livewire;

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Core\Livewire\BaseRecordManager;
use App\Modules\Journals\Domain\SupervisionLog\Actions\ReviewLogAction;
use App\Modules\Journals\Domain\SupervisionLog\Data\ReviewLogData;
use App\Modules\Journals\Domain\SupervisionLog\Models\SupervisionLog;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use TallStackUi\Traits\Interactions;

class SupervisorReviewManager extends BaseRecordManager
{
    use Interactions;

    public array $sortBy = ['column' => 'date', 'direction' => 'desc'];

    public bool $showReviewModal = false;

    public ?string $reviewTarget = null;

    public string $feedback = '';

    public function headers(): array
    {
        return [
            ['index' => 'registration.student.name', 'label' => __('journals.student'), 'sortable' => true],
            ['index' => 'date', 'label' => __('journals.date'), 'sortable' => true],
            ['index' => 'topic', 'label' => __('journals.topic')],
            ['index' => 'status', 'label' => __('journals.status')],
            ['index' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function applySearch(Builder $query): Builder
    {
        $term = '%'.$this->search.'%';

        return $query->where(function (Builder $q) use ($term) {
            $q->where('topic', 'like', $term)
                ->orWhere('notes', 'like', $term)
                ->orWhere('status', 'like', $term)
                ->orWhereHas('registration.student', fn (Builder $s) => $s->where('name', 'like', $term));
        });
    }

    protected function query(): Builder
    {
        return SupervisionLog::query()
            ->where('supervisor_id', auth()->id())
            ->with(['registration.student']);
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
            $action->execute(new ReviewLogData(
                logId: $log->id,
                supervisorId: auth()->id(),
                feedback: $this->feedback,
            ));
            $this->toast()->success(__('journals.log_reviewed'))->send();
        } catch (RejectedException $e) {
            $this->toast()->error($e->getMessage())->send();
        }

        $this->showReviewModal = false;
        $this->reviewTarget = null;
    }

    #[Layout('ui::layouts.app')]
    public function render(): View
    {
        return view('journals.supervision-log.supervisor-review-manager');
    }
}
