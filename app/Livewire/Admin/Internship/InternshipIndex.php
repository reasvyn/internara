<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Internship;

use App\Actions\Internship\CreateInternshipAction;
use App\Actions\Internship\DeleteInternshipAction;
use App\Actions\Internship\UpdateInternshipAction;
use App\Models\Internship;
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
    public string $status = 'active';

    public string $search = '';

    protected $queryString = ['search'];

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:internships,name,' . $this->internshipId],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:active,completed,draft'],
        ];
    }

    public function create(): void
    {
        $this->reset(['internshipId', 'name', 'start_date', 'end_date', 'description']);
        $this->status = 'active';
        $this->showModal = true;
    }

    public function edit(Internship $internship): void
    {
        $this->internshipId = $internship->id;
        $this->name = $internship->name;
        $this->start_date = $internship->start_date->format('Y-m-d');
        $this->end_date = $internship->end_date->format('Y-m-d');
        $this->description = $internship->description ?? '';
        $this->status = $internship->status;
        $this->showModal = true;
    }

    public function save(CreateInternshipAction $create, UpdateInternshipAction $update): void
    {
        $validated = $this->validate();

        if ($this->internshipId) {
            $internship = Internship::findOrFail($this->internshipId);
            $update->execute($internship, $validated);
            session()->flash('success', 'Internship batch updated successfully.');
        } else {
            $create->execute($validated);
            session()->flash('success', 'Internship batch created successfully.');
        }

        $this->showModal = false;
        $this->reset(['internshipId', 'name', 'start_date', 'end_date', 'description', 'status']);
    }

    public function delete(Internship $internship, DeleteInternshipAction $deleteAction): void
    {
        $deleteAction->execute($internship);
        session()->flash('success', 'Internship batch deleted successfully.');
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $internships = Internship::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->latest('start_date')
            ->paginate(10);

        return view('livewire.admin.internship.internship-index', [
            'internships' => $internships,
        ]);
    }
}
