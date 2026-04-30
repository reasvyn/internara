<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Internship;

use App\Actions\Internship\CreatePlacementAction;
use App\Actions\Internship\DeletePlacementAction;
use App\Actions\Internship\UpdatePlacementAction;
use App\Models\Internship;
use App\Models\InternshipCompany;
use App\Models\InternshipPlacement;
use Illuminate\Support\Facades\DB;
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
        return InternshipCompany::orderBy('name')->get(['id', 'name'])->map(fn($c) => ['id' => $c->id, 'name' => $c->name]);
    }

    #[Computed]
    public function internships()
    {
        return Internship::whereIn('status', ['published', 'active'])->orderBy('name')->get(['id', 'name'])->map(fn($i) => ['id' => $i->id, 'name' => $i->name]);
    }

    #[Computed]
    public function stats(): array
    {
        $placements = InternshipPlacement::query();

        return [
            'total' => $placements->count(),
            'total_quota' => $placements->sum('quota'),
            'filled' => $placements->sum('filled_quota'),
            'available' => $placements->sum(DB::raw('quota - filled_quota')),
        ];
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:internship_companies,id'],
            'internship_id' => ['required', 'exists:internships,id'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
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
            flash()->success(__('placement.update_success'));
        } else {
            $create->execute($validated);
            flash()->success(__('placement.save_success'));
        }

        $this->showModal = false;
        $this->reset(['placementId', 'company_id', 'internship_id', 'name', 'address', 'quota', 'description']);
    }

    public function delete(InternshipPlacement $placement, DeletePlacementAction $deleteAction): void
    {
        if ($placement->registrations()->exists()) {
            flash()->error(__('placement.delete_blocked'));
            return;
        }

        $deleteAction->execute($placement);
        flash()->success(__('placement.delete_success'));
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $placements = InternshipPlacement::query()
            ->with(['company', 'internship'])
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhereHas('company', fn($q) => $q->where('name', 'like', "%{$this->search}%")))
            ->latest()
            ->paginate(10);

        return view('livewire.admin.internship.placement-index', [
            'placements' => $placements,
        ]);
    }
}
