<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Internship;

use App\Actions\Internship\CreateInternshipAction;
use App\Actions\Internship\DeleteInternshipAction;
use App\Actions\Internship\UpdateInternshipAction;
use App\Enums\InternshipStatus;
use App\Livewire\BaseRecordManager;
use App\Models\Internship;
use App\Models\InternshipPlacement;
use App\Models\InternshipRegistration;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

/**
 * Modernized Internship Manager using BaseRecordManager pattern.
 */
class InternshipIndex extends BaseRecordManager
{
    public bool $showModal = false;

    public array $formData = [
        'id' => null,
        'name' => '',
        'start_date' => '',
        'end_date' => '',
        'description' => '',
        'status' => 'draft',
    ];

    /**
     * Define columns and sorting.
     */
    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => __('internship.batch_name'), 'sortable' => true],
            ['key' => 'start_date', 'label' => __('internship.start_date'), 'sortable' => true],
            ['key' => 'end_date', 'label' => __('internship.end_date'), 'sortable' => true],
            ['key' => 'status', 'label' => __('internship.status'), 'sortable' => true],
            ['key' => 'actions', 'label' => ''],
        ];
    }

    /**
     * Base query for internships.
     */
    protected function query(): Builder
    {
        return Internship::query()
            ->withCount(['placements', 'registrations']);
    }

    /**
     * Search implementation.
     */
    protected function applySearch(Builder $query): Builder
    {
        return $query->where('name', 'like', "%{$this->search}%");
    }

    #[Computed]
    public function statusOptions(): array
    {
        return collect(InternshipStatus::cases())->map(fn ($s) => [
            'id' => $s->value,
            'name' => __("internship.statuses.{$s->value}"),
        ])->toArray();
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'total' => Internship::count(),
            'active' => Internship::where('status', InternshipStatus::ACTIVE->value)->count(),
            'total_placements' => InternshipPlacement::count(),
            'total_registrations' => InternshipRegistration::count(),
        ];
    }

    // --- Record Actions ---

    public function create(): void
    {
        $this->resetErrorBag();
        $this->formData = [
            'id' => null,
            'name' => '',
            'start_date' => '',
            'end_date' => '',
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
            'start_date' => $internship->start_date->format('Y-m-d'),
            'end_date' => $internship->end_date->format('Y-m-d'),
            'description' => $internship->description ?? '',
            'status' => $internship->status->value,
        ];
        $this->showModal = true;
    }

    public function save(CreateInternshipAction $create, UpdateInternshipAction $update): void
    {
        $validStatuses = collect(InternshipStatus::cases())->map(fn ($s) => $s->value)->toArray();

        $this->validate([
            'formData.name' => ['required', 'string', 'max:255', 'unique:internships,name,'.($this->formData['id'] ?? 'NULL')],
            'formData.start_date' => ['required', 'date'],
            'formData.end_date' => ['required', 'date', 'after:formData.start_date'],
            'formData.description' => ['nullable', 'string'],
            'formData.status' => ['required', 'string', 'in:'.implode(',', $validStatuses)],
        ]);

        if ($this->formData['id']) {
            $internship = Internship::findOrFail($this->formData['id']);
            $update->execute($internship, $this->formData);
            $this->success(__('internship.update_success'));
        } else {
            $create->execute($this->formData);
            $this->success(__('internship.save_success'));
        }

        $this->showModal = false;
    }

    public function delete(Internship $internship, DeleteInternshipAction $deleteAction): void
    {
        if ($internship->placements()->exists() || $internship->registrations()->exists()) {
            $this->error(__('internship.delete_blocked'));

            return;
        }

        $deleteAction->execute($internship);
        $this->success(__('internship.delete_success'));
    }

    // --- Mass Actions ---

    public function closeAllFiltered(): void
    {
        $this->performMassAction('Close All Filtered', function ($query) {
            $query->update(['status' => InternshipStatus::COMPLETED->value]);
        });
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.admin.internship.internship-index');
    }
}
