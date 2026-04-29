<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Internship;

use App\Actions\Internship\CreatePlacementAction;
use App\Actions\Internship\DeletePlacementAction;
use App\Actions\Internship\UpdatePlacementAction;
use App\Models\Internship;
use App\Models\InternshipCompany;
use App\Models\InternshipPlacement;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class PlacementIndex extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public string $placementId = '';
    public string $company_id = '';
    public string $internship_id = '';
    public string $name = '';
    public string $address = '';
    public ?int $quota = null;
    public string $description = '';

    public string $search = '';

    protected $queryString = ['search'];

    #[Computed]
    public function companies()
    {
        return InternshipCompany::orderBy('name')->get();
    }

    #[Computed]
    public function internships()
    {
        return Internship::where('status', 'active')->orderBy('name')->get();
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:internship_companies,id'],
            'internship_id' => ['required', 'exists:internships,id'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'quota' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function create(): void
    {
        $this->reset(['placementId', 'company_id', 'internship_id', 'name', 'address', 'quota', 'description']);
        $this->showModal = true;
    }

    public function edit(InternshipPlacement $placement): void
    {
        $this->placementId = $placement->id;
        $this->company_id = $placement->company_id;
        $this->internship_id = $placement->internship_id;
        $this->name = $placement->name;
        $this->address = $placement->address ?? '';
        $this->quota = $placement->quota;
        $this->description = $placement->description ?? '';
        $this->showModal = true;
    }

    public function save(CreatePlacementAction $create, UpdatePlacementAction $update): void
    {
        $validated = $this->validate();

        if ($this->placementId) {
            $placement = InternshipPlacement::findOrFail($this->placementId);
            $update->execute($placement, $validated);
            session()->flash('success', 'Placement updated successfully.');
        } else {
            $create->execute($validated);
            session()->flash('success', 'Placement created successfully.');
        }

        $this->showModal = false;
        $this->reset(['placementId', 'company_id', 'internship_id', 'name', 'address', 'quota', 'description']);
    }

    public function delete(InternshipPlacement $placement, DeletePlacementAction $deleteAction): void
    {
        $deleteAction->execute($placement);
        session()->flash('success', 'Placement deleted successfully.');
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $placements = InternshipPlacement::query()
            ->with(['company', 'internship'])
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhereHas('company', fn($q) => $q->where('name', 'like', "%{$this->search}%")))
            ->paginate(10);

        return view('livewire.admin.internship.placement-index', [
            'placements' => $placements,
        ]);
    }
}
