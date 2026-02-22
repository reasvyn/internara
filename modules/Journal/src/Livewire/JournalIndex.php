<?php

declare(strict_types=1);

namespace Modules\Journal\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class JournalIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $date = '';

    public bool $journalDetailModal = false;

    public ?JournalEntry $selectedEntry = null;

    protected JournalService $journalService;

    /**
     * Inject dependencies.
     */
    public function boot(JournalService $journalService): void
    {
        $this->journalService = $journalService;

        // Gating System: Check if student has completed mandatory guidance
        if (auth()->check() && auth()->user()->hasRole('student')) {
            $guidanceService = app(\Modules\Guidance\Services\Contracts\HandbookService::class);
            $settingService = app(\Modules\Setting\Services\Contracts\SettingService::class);

            if (
                $settingService->getValue('feature_guidance_enabled', true) &&
                ! $guidanceService->hasCompletedMandatory(auth()->id())
            ) {
                flash()->warning(__('guidance::messages.must_complete_guidance'));

                $this->redirect(route('student.dashboard'), navigate: true);
            }
        }
    }

    /**
     * Get the journals based on user role.
     */
    #[Computed]
    public function journals()
    {
        $user = auth()->user();
        $filters = [
            'search' => $this->search,
            'date' => $this->date,
            'sort_by' => 'date',
            'sort_dir' => 'desc',
        ];

        // Ensure all columns needed by @scope and policy checks are loaded
        $columns = ['id', 'registration_id', 'student_id', 'date', 'work_topic', 'created_at'];

        if ($user->hasRole('student')) {
            $filters['student_id'] = $user->id;
        } elseif ($user->hasRole(['teacher', 'mentor'])) {
            $query = $this->journalService->query($filters, $columns);
            $query->with([
                'student:id,name', 
                'registration:id,placement_id', 
                'registration.placement:id,company_id', 
                'registration.placement.company:id,name'
            ]);
            $query->whereHas('registration', function ($q) use ($user) {
                $q->where('teacher_id', $user->id)->orWhere('mentor_id', $user->id);
            });

            return $query->paginate(10);
        }

        return $this->journalService->paginate($filters, 10, $columns);
    }

    /**
     * Get the current week's journal status for students.
     */
    #[Computed]
    public function weekGlance(): array
    {
        if (! auth()->user()->hasRole('student')) {
            return [];
        }

        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        $entries = $this->journalService
            ->query([
                'student_id' => auth()->id(),
                'start_date' => $startOfWeek->format('Y-m-d'),
                'end_date' => $endOfWeek->format('Y-m-d'),
            ], ['id', 'date'])
            ->get()
            ->keyBy(fn ($e) => $e->date->format('Y-m-d'));

        $days = [];
        for ($date = $startOfWeek->copy(); $date <= $endOfWeek; $date->addDay()) {
            if ($date->isWeekend()) {
                continue;
            }

            $key = $date->format('Y-m-d');
            $entry = $entries->get($key);

            $days[] = [
                'date' => $date->copy(),
                'label' => $date->translatedFormat('D'),
                'day' => $date->format('d'),
                'status' => $entry ? $entry->latestStatus()?->name ?? 'draft' : 'empty',
                'id' => $entry?->id,
            ];
        }

        return $days;
    }

    /**
     * Show journal detail in modal.
     */
    public function showDetail(string $id): void
    {
        $this->selectedEntry = $this->journalService->find($id);

        $this->authorize('view', $this->selectedEntry);

        $this->journalDetailModal = true;
    }

    /**
     * Approve a journal entry.
     */
    public function approve(string $id): void
    {
        $entry = $this->journalService->find($id);

        $this->authorize('validate', $entry);

        $this->journalService->approve($id);

        if ($this->selectedEntry && $this->selectedEntry->id === $id) {
            $this->selectedEntry = $this->journalService->find($id);
        }

        flash()->success(__('shared::messages.record_approved'));
    }

    /**
     * Reject a journal entry.
     */
    public function reject(string $id, string $reason = 'Rejected by supervisor'): void
    {
        $entry = $this->journalService->find($id);

        $this->authorize('validate', $entry);

        $this->journalService->reject($id, $reason);

        if ($this->selectedEntry && $this->selectedEntry->id === $id) {
            $this->selectedEntry = $this->journalService->find($id);
        }

        flash()->error(__('shared::messages.record_rejected'));
    }

    public function render(): View
    {
        return view('journal::livewire.journal-index')->layout('ui::components.layouts.dashboard', [
            'title' => __('journal::ui.index.title') . ' | ' . setting('brand_name', setting('app_name')),
        ]);
    }
}
