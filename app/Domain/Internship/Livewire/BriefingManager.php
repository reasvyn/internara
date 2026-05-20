<?php

declare(strict_types=1);

namespace App\Domain\Internship\Livewire;

use App\Domain\Core\Livewire\BaseRecordManager;
use App\Domain\Internship\Actions\CreateBriefingAction;
use App\Domain\Internship\Actions\OverrideBriefingAttendanceAction;
use App\Domain\Internship\Actions\RecordBriefingAttendanceAction;
use App\Domain\Internship\Models\Briefing;
use App\Domain\Internship\Models\BriefingAttendance;
use App\Domain\Internship\Models\Internship;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

class BriefingManager extends BaseRecordManager
{
    public bool $showModal = false;

    public bool $showAttendanceModal = false;

    public ?string $attendanceBriefingId = null;

    public array $attendees = [];

    public array $formData = [
        'id' => null,
        'title' => '',
        'description' => '',
        'date' => '',
        'location' => '',
        'is_mandatory' => true,
        'internship_id' => '',
    ];

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
        $this->formData = [
            'id' => null, 'title' => '', 'description' => '', 'date' => '',
            'location' => '', 'is_mandatory' => true, 'internship_id' => '',
        ];
        $this->showModal = true;
    }

    public function edit(Briefing $briefing): void
    {
        $this->resetErrorBag();
        $this->formData = [
            'id' => $briefing->id,
            'title' => $briefing->title,
            'description' => $briefing->description ?? '',
            'date' => $briefing->date?->format('Y-m-d\TH:i') ?? '',
            'location' => $briefing->location ?? '',
            'is_mandatory' => $briefing->is_mandatory,
            'internship_id' => $briefing->internship_id,
        ];
        $this->showModal = true;
    }

    public function save(CreateBriefingAction $create): void
    {
        $this->validate([
            'formData.title' => ['required', 'string', 'max:255'],
            'formData.description' => ['nullable', 'string', 'max:5000'],
            'formData.date' => ['required', 'date'],
            'formData.location' => ['nullable', 'string', 'max:255'],
            'formData.is_mandatory' => ['boolean'],
            'formData.internship_id' => ['required', 'exists:internships,id'],
        ]);

        $create->execute([
            ...$this->formData,
            'is_mandatory' => $this->formData['is_mandatory'] ?? true,
            'created_by' => auth()->id(),
        ]);

        flash()->success(__('briefing.save_success'));
        $this->showModal = false;
    }

    public function manageAttendance(Briefing $briefing): void
    {
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

    #[Layout('layouts::app')]
    public function render(): View
    {
        return view('internship.briefing-manager');
    }
}
