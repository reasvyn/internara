<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Placement\Livewire;

use App\Modules\Auth\Domain\Permissions\Enums\Role;
use App\Modules\Core\Livewire\BaseFormView;
use App\Modules\Enrollment\Domain\Placement\Actions\DirectPlacementAction;
use App\Modules\Enrollment\Domain\Placement\Livewire\Forms\DirectPlacementForm;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\User\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use TallStackUi\Traits\Interactions;

class DirectPlacementManager extends BaseFormView
{
    use AuthorizesRequests;
    use Interactions;

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
        $this->authorize('create', Registration::class);

        $this->form->validate();

        $this->handleSave(function () use ($placementAction) {
            $student = User::findOrFail($this->form->student_id);

            $placementAction->execute($student, [
                'placement_id' => $this->form->placement_id,
                'academic_year' => $this->form->academic_year,
                'mentor_ids' => $this->form->mentor_ids,
            ]);

            $this->toast()->success(__('placement.direct_placement.success'))->send();
            $this->form->reset();
        });
    }

    public function render(): View
    {
        return view('enrollment.placement.direct-placement-manager');
    }
}
