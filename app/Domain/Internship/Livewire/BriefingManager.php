<?php

declare(strict_types=1);

namespace App\Domain\Internship\Livewire;

use App\Domain\Core\Livewire\BaseRecordManager;
use App\Domain\Internship\Actions\CreateBriefingAction;
use App\Domain\Internship\Actions\OverrideBriefingAttendanceAction;
use App\Domain\Internship\Actions\RecordBriefingAttendanceAction;
use App\Domain\Internship\Livewire\Forms\BriefingForm;
use App\Domain\Internship\Models\Briefing;
use App\Domain\Internship\Models\BriefingAttendance;
use App\Domain\Internship\Models\Internship;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

class BriefingManager extends BaseRecordManager
{
    use AuthorizesRequests;

    public function boot(): void
    {
        $this->authorize('viewAny', Internship::class);
    }

    public bool $showModal = false;

    public bool $showAttendanceModal = false;

    public ?string $attendanceBriefingId = null;

    public array $attendees = [];

    public BriefingForm $form;

    public function headers(): array
    {
        return [
            ['key' => 'title', 'label' => __('briefing.title_field'), 'sortable' => true],
            ['key' => 'date', 'label' => __('briefing.date'), 'sortable' => true],
            ['key' => 'location', 'label' => __('briefing.location')],
            ['key' => 'is_mandatory', 'label' => __('briefing.is_mandatory'), 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return Briefing::query()->withCount('attendances');
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where('title', 'like', "%{$this->search}%");
    }

    #[Computed]
    public function internships(): array
    {
        return Internship::query()->orderBy('name')->get(['id', 'name'])->toArray();
    }

    public function create(): void
    {
        $this->resetErrorBag();
        $this->form->reset();
        $this->showModal = true;
    }

    public function edit(string $id): void
    {
        $briefing = Briefing::findOrFail($id);

        $this->resetErrorBag();
        $this->form->fill([
            'id' => $briefing->id,
            'title' => $briefing->title,
            'description' => $briefing->description ?? '',
            'date' => $briefing->date?->format('Y-m-d\TH:i') ?? '',
            'location' => $briefing->location ?? '',
            'is_mandatory' => $briefing->is_mandatory,
            'internship_id' => $briefing->internship_id,
        ]);
        $this->showModal = true;
    }

    public function save(CreateBriefingAction $create): void
    {
        $this->form->validate();

        $create->execute([
            ...$this->form->all(),
            'created_by' => auth()->id(),
        ]);

        flash()->success(__('briefing.save_success'));
        $this->showModal = false;
    }

    public function manageAttendance(string $id): void
    {
        $briefing = Briefing::findOrFail($id);
        $this->attendanceBriefingId = $briefing->id;
        $this->attendees = BriefingAttendance::query()
            ->where('briefing_id', $briefing->id)
            ->get()
            ->map(fn ($a) => ['user_id' => $a->user_id, 'attended' => $a->attended, 'notes' => $a->notes])
            ->toArray();
        $this->showAttendanceModal = true;
    }

    public function saveAttendance(RecordBriefingAttendanceAction $recordAction, OverrideBriefingAttendanceAction $overrideAction): void
    {
        $briefing = Briefing::findOrFail($this->attendanceBriefingId);
        $recordAction->execute($briefing, $this->attendees);
        flash()->success(__('briefing.attendance_saved'));
        $this->showAttendanceModal = false;
    }

    #[Layout('shared::layouts.app')]
    public function render(): View
    {
        return view('internship.briefing-manager');
    }
}
