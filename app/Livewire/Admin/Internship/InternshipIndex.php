<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Internship;

use App\Actions\Internship\CreateInternshipAction;
use App\Actions\Internship\DeleteInternshipAction;
use App\Actions\Internship\UpdateInternshipAction;
use App\Enums\InternshipStatus;
use App\Models\Internship;
use App\Models\InternshipPlacement;
use App\Models\InternshipRegistration;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class InternshipIndex extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public string $internshipId = '';
    public string $name = '';
    public string $start_date = '';
    public string $end_date = '';
    public string $description = '';
    public string $status = InternshipStatus::DRAFT->value;

    public string $search = '';

    protected $queryString = ['search'];

    #[Computed]
    public function statusOptions(): array
    {
        return collect(InternshipStatus::cases())->map(fn($s) => [
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

    public function rules(): array
    {
        $validStatuses = collect(InternshipStatus::cases())->map(fn($s) => $s->value)->toArray();

        return [
            'name' => ['required', 'string', 'max:255', 'unique:internships,name,' . $this->internshipId],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:' . implode(',', $validStatuses)],
        ];
    }

    public function create(): void
    {
        $this->reset(['internshipId', 'name', 'start_date', 'end_date', 'description']);
        $this->status = InternshipStatus::DRAFT->value;
        $this->showModal = true;
    }

    public function edit(Internship $internship): void
    {
        $this->internshipId = $internship->id;
        $this->name = $internship->name;
        $this->start_date = $internship->start_date->format('Y-m-d');
        $this->end_date = $internship->end_date->format('Y-m-d');
        $this->description = $internship->description ?? '';
        $this->status = $internship->status->value;
        $this->showModal = true;
    }

    public function save(CreateInternshipAction $create, UpdateInternshipAction $update): void
    {
        $validated = $this->validate();

        if ($this->internshipId) {
            $internship = Internship::findOrFail($this->internshipId);
            $update->execute($internship, $validated);
            flash()->success(__('internship.update_success'));
        } else {
            $create->execute($validated);
            flash()->success(__('internship.save_success'));
        }

        $this->showModal = false;
        $this->reset(['internshipId', 'name', 'start_date', 'end_date', 'description', 'status']);
    }

    public function delete(Internship $internship, DeleteInternshipAction $deleteAction): void
    {
        if ($internship->placements()->exists() || $internship->registrations()->exists()) {
            flash()->error(__('internship.delete_blocked'));
            return;
        }

        $deleteAction->execute($internship);
        flash()->success(__('internship.delete_success'));
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $internships = Internship::query()
            ->withCount(['placements', 'registrations'])
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->latest('start_date')
            ->paginate(10);

        return view('livewire.admin.internship.internship-index', [
            'internships' => $internships,
        ]);
    }
}
