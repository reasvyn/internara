<?php

declare(strict_types=1);

namespace App\Enrollment\Placement\Livewire;

use App\Auth\Permissions\Enums\Role;
use App\Enrollment\Placement;
use App\Enrollment\Placement\Actions\DirectPlacementAction;
use App\Enrollment\Placement\Livewire\Forms\DirectPlacementForm;
use App\Enrollment\Registration\Models\Registration;
use App\User\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Component;

class DirectPlacementManager extends Component
{
    use AuthorizesRequests;

    public DirectPlacementForm $form;

    public function boot(): void
    {
        $this->authorize('create', Registration::class);
    }

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
        return User::role(['teacher', 'supervisor'])->get();
    }

    public function submit(DirectPlacementAction $placementAction): void
    {
        $this->form->validate();

        $student = User::findOrFail($this->form->student_id);

        try {
            $placementAction->execute($student, [
                'placement_id' => $this->form->placement_id,
                'academic_year' => $this->form->academic_year,
                'mentor_ids' => $this->form->mentor_ids,
            ]);

            flash()->success(__('placement.direct_placement.success'));
            $this->form->reset();
        } catch (\Throwable $e) {
            flash()->error($e->getMessage());
        }
    }

    public function render(): View
    {
        return view('enrollment.placement.direct-placement-manager');
    }
}
