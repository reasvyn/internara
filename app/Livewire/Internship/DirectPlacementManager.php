<?php

declare(strict_types=1);

namespace App\Livewire\Internship;

use App\Actions\Internship\DirectPlacementAction;
use App\Enums\Auth\Role;
use App\Models\Mentor;
use App\Models\Placement;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Mary\Traits\Toast;

class DirectPlacementManager extends Component
{
    use Toast;

    public string $student_id = '';

    public string $placement_id = '';

    public string $academic_year = '';

    public array $mentor_ids = [];

    #[Computed]
    public function students(): Collection
    {
        return User::role(Role::STUDENT->value)
            ->whereDoesntHave('registrations', function ($q) {
                $q->currentStatus('active');
            })
            ->get();
    }

    #[Computed]
    public function placements(): Collection
    {
        return Placement::with(['company', 'internship'])
            ->get()
            ->filter(fn ($p) => ! $p->asPlacementCapacity()->isFull());
    }

    #[Computed]
    public function mentors(): Collection
    {
        return Mentor::with('user')->where('is_active', true)->get();
    }

    public function submit(DirectPlacementAction $placementAction): void
    {
        $this->validate([
            'student_id' => 'required|exists:users,id',
            'placement_id' => 'required|exists:internship_placements,id',
            'academic_year' => 'required',
            'mentor_ids' => 'nullable|array',
            'mentor_ids.*' => 'exists:mentors,id',
        ]);

        $student = User::findOrFail($this->student_id);

        $placementAction->execute($student, [
            'placement_id' => $this->placement_id,
            'academic_year' => $this->academic_year,
            'mentor_ids' => $this->mentor_ids,
        ]);

        $this->success('Student placed successfully.');
        $this->reset(['student_id', 'placement_id', 'mentor_ids']);
    }

    public function render()
    {
        return <<<'HTML'
        <div>
            <x-mary-header title="Direct Placement" subtitle="Manually assign students to industry partners" separator />

            <x-mary-card>
                <x-mary-form wire:submit="submit">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-mary-select
                            label="Target Student"
                            wire:model="student_id"
                            :options="$this->students"
                            placeholder="Search and select student"
                            icon="o-user" />

                        <x-mary-input
                            label="Academic Year"
                            wire:model="academic_year"
                            placeholder="e.g. 2025/2026" />

                        <x-mary-select
                            label="Target Placement"
                            wire:model="placement_id"
                            :options="$this->placements"
                            placeholder="Select industry partner"
                            class="md:col-span-2"
                            icon="o-briefcase" />

                        <x-mary-select
                            label="Assigned Mentors"
                            wire:model="mentor_ids"
                            :options="$this->mentors"
                            placeholder="Select mentors"
                            multiple
                            class="md:col-span-2"
                            icon="o-user-group" />
                    </div>

                    <x-slot:actions>
                        <x-mary-button label="Assign Placement" type="submit" icon="o-check" class="btn-primary" />
                    </x-slot:actions>
                </x-mary-form>
            </x-mary-card>
        </div>
        HTML;
    }
}
