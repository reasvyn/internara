<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Internship;

use App\Actions\Internship\DirectPlacementAction;
use App\Enums\Role;
use App\Models\InternshipPlacement;
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

    /**
     * Get students who are NOT yet registered in any active internship.
     */
    #[Computed]
    public function students(): Collection
    {
        return User::role(Role::STUDENT->value)
            ->whereDoesntHave('registrations', function ($q) {
                $q->currentStatus('active');
            })
            ->get();
    }

    /**
     * Get available placements.
     */
    #[Computed]
    public function placements(): Collection
    {
        return InternshipPlacement::with(['company', 'internship'])
            ->get()
            ->filter(fn($p) => !$p->isFull());
    }

    public function submit(DirectPlacementAction $placementAction): void
    {
        $this->validate([
            'student_id' => 'required|exists:users,id',
            'placement_id' => 'required|exists:internship_placements,id',
            'academic_year' => 'required',
        ]);

        $student = User::findOrFail($this->student_id);

        $placementAction->execute($student, [
            'placement_id' => $this->placement_id,
            'academic_year' => $this->academic_year,
        ]);

        $this->success('Student placed successfully.');
        $this->reset(['student_id', 'placement_id']);
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
