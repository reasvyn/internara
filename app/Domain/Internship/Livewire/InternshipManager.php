<?php

declare(strict_types=1);

namespace App\Domain\Internship\Livewire;

use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Core\Livewire\BaseRecordManager;
use App\Domain\Internship\Actions\BatchUpdateInternshipStatusAction;
use App\Domain\Internship\Actions\CheckCloseReadinessAction;
use App\Domain\Internship\Actions\CreateInternshipAction;
use App\Domain\Internship\Actions\DeleteInternshipAction;
use App\Domain\Internship\Actions\UpdateInternshipAction;
use App\Domain\Internship\Enums\InternshipStatus;
use App\Domain\Internship\Models\Internship;
use App\Domain\Placement\Models\Placement;
use App\Domain\Registration\Models\Registration;
use App\Domain\School\Models\AcademicYear;
use App\Domain\Shared\Support\CsvHandler;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;

class InternshipManager extends BaseRecordManager
{
    use WithFileUploads;

    public bool $showModal = false;

    public bool $showConfirm = false;

    public string $confirmMessage = '';

    public string $confirmType = '';

    public ?string $confirmTarget = null;

    public $importFile;

    public ?array $readinessResults = null;

    public ?string $readinessInternshipId = null;

    public array $formData = [
        'id' => null,
        'name' => '',
        'academic_year_id' => '',
        'start_date' => '',
        'end_date' => '',
        'registration_start_date' => '',
        'registration_end_date' => '',
        'description' => '',
        'status' => 'draft',
    ];

    public function boot(): void
    {
        if (
            ! auth()
                ->user()
                ?->hasAnyRole(['super_admin', 'admin'])
        ) {
            abort(403, 'Unauthorized access.');
        }
    }

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => __('internship.batch_name'), 'sortable' => true],
            ['key' => 'start_date', 'label' => __('internship.start_date'), 'sortable' => true],
            ['key' => 'end_date', 'label' => __('internship.end_date'), 'sortable' => true],
            ['key' => 'status', 'label' => __('internship.status'), 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return Internship::query()
            ->with('academicYear')
            ->withCount(['placements', 'registrations']);
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where('name', 'like', "%{$this->search}%");
    }

    protected function applyFilters(Builder $query): Builder
    {
        return $query
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($this->filters['academic_year_id'] ?? null, fn ($q, $v) => $q->where('academic_year_id', $v))
            ->when($this->filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('start_date', '>=', $v))
            ->when($this->filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('end_date', '<=', $v));
    }

    #[Computed]
    public function statusOptions(): array
    {
        return collect(InternshipStatus::cases())
            ->map(fn ($s) => [
                'id' => $s->value,
                'name' => __("internship.statuses.{$s->value}"),
            ])
            ->toArray();
    }

    #[Computed]
    public function academicYears(): array
    {
        return AcademicYear::orderByDesc('start_date')
            ->get()
            ->map(fn ($y) => [
                'id' => $y->id,
                'name' => $y->name,
            ])
            ->prepend(['id' => '', 'name' => __('internship.select_academic_year')])
            ->toArray();
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'total' => Internship::count(),
            'active' => Internship::where('status', InternshipStatus::ACTIVE->value)->count(),
            'total_placements' => Placement::count(),
            'total_registrations' => Registration::count(),
        ];
    }

    // --- Record Actions ---

    public function create(): void
    {
        $this->resetErrorBag();
        $activeYear = AcademicYear::where('is_active', true)->first();
        $this->formData = [
            'id' => null,
            'name' => '',
            'academic_year_id' => $activeYear?->id ?? '',
            'start_date' => '',
            'end_date' => '',
            'registration_start_date' => '',
            'registration_end_date' => '',
            'description' => '',
            'status' => InternshipStatus::DRAFT->value,
        ];
        $this->showModal = true;
    }

    public function edit(Internship $internship): void
    {
        $this->resetErrorBag();
        $this->formData = [
            'id' => $internship->id,
            'name' => $internship->name,
            'academic_year_id' => $internship->academic_year_id ?? '',
            'start_date' => $internship->start_date->format('Y-m-d'),
            'end_date' => $internship->end_date->format('Y-m-d'),
            'registration_start_date' => $internship->registration_start_date?->format('Y-m-d') ?? '',
            'registration_end_date' => $internship->registration_end_date?->format('Y-m-d') ?? '',
            'description' => $internship->description ?? '',
            'status' => $internship->status->value,
        ];
        $this->showModal = true;
    }

    public function save(CreateInternshipAction $create, UpdateInternshipAction $update): void
    {
        $validStatuses = collect(InternshipStatus::cases())->map(fn ($s) => $s->value)->toArray();

        $this->formData['registration_start_date'] = $this->formData['registration_start_date'] ?: null;
        $this->formData['registration_end_date'] = $this->formData['registration_end_date'] ?: null;

        $this->validate([
            'formData.name' => [
                'required',
                'string',
                'max:255',
                'unique:internships,name,'.($this->formData['id'] ?? 'NULL'),
            ],
            'formData.academic_year_id' => ['nullable', 'string'],
            'formData.start_date' => ['required', 'date'],
            'formData.end_date' => ['required', 'date', 'after:formData.start_date'],
            'formData.registration_start_date' => ['nullable', 'date'],
            'formData.registration_end_date' => ['nullable', 'date', 'after_or_equal:formData.registration_start_date'],
            'formData.description' => ['nullable', 'string'],
            'formData.status' => ['required', 'string', 'in:'.implode(',', $validStatuses)],
        ]);

        if ($this->formData['id']) {
            $internship = Internship::findOrFail($this->formData['id']);

            try {
                $update->execute($internship, $this->formData);
                flash()->success(__('internship.update_success'));
            } catch (RejectedException $e) {
                flash()->error($e->getMessage());

                return;
            }
        } else {
            try {
                $create->execute($this->formData);
                flash()->success(__('internship.save_success'));
            } catch (RejectedException $e) {
                flash()->error($e->getMessage());

                return;
            }
        }

        $this->showModal = false;
    }

    // --- Confirm Dialog ---

    public function askDelete(string $id): void
    {
        $internship = Internship::findOrFail($id);

        $this->confirmTarget = $id;
        $this->confirmType = 'delete';
        $this->confirmMessage = __('internship.confirm_delete', ['name' => $internship->name]);
        $this->showConfirm = true;
    }

    public function askDeleteSelected(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->confirmTarget = null;
        $this->confirmType = 'delete_selected';
        $this->confirmMessage = __('internship.confirm_delete_selected', ['count' => count($this->selectedIds)]);
        $this->showConfirm = true;
    }

    public function askCloseFiltered(): void
    {
        $this->confirmTarget = null;
        $this->confirmType = 'close_filtered';
        $this->confirmMessage = __('internship.confirm_close_filtered');
        $this->showConfirm = true;
    }

    public function confirmAction(
        DeleteInternshipAction $deleteAction,
        BatchUpdateInternshipStatusAction $batchAction,
    ): void {
        if ($this->confirmTarget === null && $this->confirmType !== 'close_filtered' && $this->confirmType !== 'delete_selected') {
            return;
        }

        try {
            match ($this->confirmType) {
                'delete' => $this->executeDelete($this->confirmTarget, $deleteAction),
                'delete_selected' => $this->executeDeleteSelected($deleteAction),
                'close_filtered' => $this->executeCloseFiltered($batchAction),
                default => null,
            };
        } catch (RejectedException $e) {
            flash()->error($e->getMessage());
        }

        $this->showConfirm = false;
        $this->confirmTarget = null;
        $this->confirmType = '';
    }

    private function executeDelete(string $id, DeleteInternshipAction $action): void
    {
        $internship = Internship::findOrFail($id);

        if (! $internship->asInternshipState()->canBeDeleted()) {
            flash()->error(__('internship.delete_blocked'));

            return;
        }

        $action->execute($internship);
        flash()->success(__('internship.delete_success'));
    }

    private function executeDeleteSelected(DeleteInternshipAction $action): void
    {
        $this->performBulkAction('Delete', function ($id) use ($action) {
            $internship = Internship::find($id);
            if ($internship && $internship->asInternshipState()->canBeDeleted()) {
                $action->execute($internship);
            }
        });
    }

    private function executeCloseFiltered(BatchUpdateInternshipStatusAction $action): void
    {
        $this->performMassAction('Close All Filtered', function ($query) use ($action) {
            $action->execute($query, InternshipStatus::COMPLETED);
        });
    }

    // --- Import / Export / Template ---

    public function updatedImportFile(): void
    {
        if ($this->importFile) {
            $this->import();
        }
    }

    public function import(CsvHandler $csv): void
    {
        $this->validate([
            'importFile' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $activeYear = AcademicYear::where('is_active', true)->first();

        $result = $csv->import($this->importFile->getRealPath(), function (array $row) use ($activeYear) {
            $name = trim($row[0] ?? '');

            if ($name === '') {
                return null;
            }

            if (Internship::where('name', $name)->exists()) {
                return 'skipped';
            }

            Internship::create([
                'name' => $name,
                'description' => trim($row[1] ?? '') ?: null,
                'academic_year_id' => $activeYear?->id,
                'start_date' => $activeYear?->start_date ?? now(),
                'end_date' => $activeYear?->end_date ?? now()->addYear(),
                'status' => InternshipStatus::DRAFT,
            ]);

            return 'created';
        });

        $this->importFile = null;

        if ($result['invalid']) {
            flash()->error(__('internship.import_invalid'));

            return;
        }

        flash()->success(__('internship.import_summary', [
            'created' => $result['created'],
            'skipped' => $result['skipped'],
        ]));
    }

    public function export(CsvHandler $csv): mixed
    {
        $internships = Internship::with('academicYear')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->get();

        return $csv->export($internships, ['name', 'description', 'status', 'start_date', 'end_date'],
            fn ($i) => [$i->name, $i->description ?? '', $i->status->value, $i->start_date->format('Y-m-d'), $i->end_date->format('Y-m-d')],
        )->send();
    }

    public function exportSelected(CsvHandler $csv): mixed
    {
        if ($this->selectedIds === []) {
            flash()->warning(__('common.actions.no_records_selected'));

            return;
        }

        $internships = Internship::with('academicYear')
            ->whereIn('id', $this->selectedIds)
            ->orderBy('name')
            ->get();

        return $csv->export($internships, ['name', 'description', 'status', 'start_date', 'end_date'],
            fn ($i) => [$i->name, $i->description ?? '', $i->status->value, $i->start_date->format('Y-m-d'), $i->end_date->format('Y-m-d')],
        )->send();
    }

    public function downloadTemplate(CsvHandler $csv): mixed
    {
        return $csv->downloadTemplate(
            ['name', 'description', 'status', 'start_date', 'end_date'],
            [__('internship.template_example_name'), __('internship.template_example_description'), 'draft', now()->format('Y-m-d'), now()->addYear()->format('Y-m-d')],
            'internships-template.csv',
        )->send();
    }

    // --- Pre-Close Readiness ---

    public function checkReadiness(string $internshipId, CheckCloseReadinessAction $action): void
    {
        $internship = Internship::findOrFail($internshipId);
        $this->readinessResults = $action->execute($internship);
        $this->readinessInternshipId = $internshipId;
    }

    public function dismissReadiness(): void
    {
        $this->readinessResults = null;
        $this->readinessInternshipId = null;
    }

    public function render(): View
    {
        return view('internship.internship-manager');
    }
}
