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

class DirectPlacementManager extends Component
{
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

        try {
            $placementAction->execute($student, [
                'placement_id' => $this->placement_id,
                'academic_year' => $this->academic_year,
                'mentor_ids' => $this->mentor_ids,
            ]);

            flash()->success(__('internship.direct_placement.success'));
            $this->reset(['student_id', 'placement_id', 'mentor_ids']);
        } catch (\RuntimeException $e) {
            flash()->error($e->getMessage());
        }
    }

    public function render()
    {
        return <<<'HTML'
        <div>
            <x-mary-header :title="__('internship.direct_placement.title')" :subtitle="__('internship.direct_placement.subtitle')" separator />

            <x-mary-card>
                <x-mary-form wire:submit="submit">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-mary-select
                            :label="__('internship.direct_placement.student')"
                            wire:model="student_id"
                            :options="$this->students"
                            :placeholder="__('internship.direct_placement.select_student')"
                            icon="o-user" />

                        <x-mary-input
                            :label="__('internship.registration_wizard.label_academic_year')"
                            wire:model="academic_year"
                            placeholder="e.g. 2025/2026" />

                        <x-mary-select
                            :label="__('internship.direct_placement.placement')"
                            wire:model="placement_id"
                            :options="$this->placements"
                            :placeholder="__('internship.direct_placement.select_placement')"
                            class="md:col-span-2"
                            icon="o-briefcase" />

                        <x-mary-select
                            :label="__('internship.direct_placement.mentors')"
                            wire:model="mentor_ids"
                            :options="$this->mentors"
                            :placeholder="__('internship.direct_placement.select_mentors')"
                            multiple
                            class="md:col-span-2"
                            icon="o-user-group" />
                    </div>

                    <x-slot:actions>
                        <x-mary-button :label="__('internship.direct_placement.assign')" type="submit" icon="o-check" class="btn-primary" />
                    </x-slot:actions>
                </x-mary-form>
            </x-mary-card>
        </div>
        HTML;
    }
}
