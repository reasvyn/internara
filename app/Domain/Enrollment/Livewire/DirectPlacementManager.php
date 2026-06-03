<?php

declare(strict_types=1);

namespace App\Domain\Enrollment\Livewire;

use App\Domain\Enrollment\Actions\DirectPlacementAction;
use App\Domain\Enrollment\Livewire\Forms\DirectPlacementForm;
use App\Domain\Enrollment\Models\Placement;
use App\Domain\Enrollment\Models\Registration;
use App\Domain\Guidance\Aggregates\Mentor\Models\Mentor;
use App\Domain\User\Enums\Role;
use App\Domain\User\Models\User;
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
        return Mentor::with('user')->where('is_active', true)->get();
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
        return view('enrollment.direct-placement-manager');
    }
}
