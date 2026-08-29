<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Placement\Livewire;

use App\Modules\Core\Livewire\BaseFormView;
use App\Modules\Enrollment\Domain\Placement\Actions\RequestPlacementChangeAction;
use App\Modules\Enrollment\Domain\Placement\Livewire\Forms\PlacementChangeForm;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Placement\Models\PlacementChangeRequest;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use TallStackUi\Traits\Interactions;

class StudentPlacementChangeRequest extends BaseFormView
{
    use AuthorizesRequests;
    use Interactions;

    public ?string $registrationId = null;

    public PlacementChangeForm $form;

    public ?PlacementChangeRequest $pendingRequest = null;

    public function boot(): void
    {
        $this->authorize('create', PlacementChangeRequest::class);
    }

    public function mount(): void
    {
        $registration = Registration::query()
            ->whereHas('mentee', fn (Builder $q) => $q->where('user_id', auth()->id()))
            ->where('status', 'active')
            ->with('placement.company', 'internship')
            ->first();

        if ($registration) {
            $this->registrationId = $registration->id;
            $this->pendingRequest = PlacementChangeRequest::where(
                'registration_id',
                $registration->id,
            )
                ->where('status', 'pending')
                ->first();
        }
    }

    public function submit(RequestPlacementChangeAction $action): void
    {
        $this->form->validate();

        $registration = Registration::findOrFail($this->registrationId);

        $this->handleSave(function () use ($registration, $action) {
            $action->execute($registration, [
                'to_placement_id' => $this->form->to_placement_id,
                'reason' => $this->form->reason,
                'requested_by' => auth()->id(),
            ]);

            $this->toast()->success(__('placement_change.request_success'))->send();
            $this->pendingRequest = PlacementChangeRequest::where('registration_id', $registration->id)
                ->where('status', 'pending')
                ->first();
            $this->form->reset();
        });
    }

    #[Layout('ui::layouts.app')]
    public function render(): View
    {
        $registration = $this->registrationId
            ? Registration::with('placement.company', 'internship.placements.company')->find(
                $this->registrationId,
            )
            : null;

        $availablePlacements = collect();
        if ($registration) {
            $availablePlacements = Placement::query()
                ->where('internship_id', $registration->internship_id)
                ->where('id', '!=', $registration->placement_id)
                ->whereHas('company')
                ->with('company')
                ->get()
                ->filter(fn (Placement $p) => $p->asPlacementCapacity()->hasAvailableSlots());
        }

        return view('enrollment.placement.student-placement-change-request', [
            'registration' => $registration,
            'availablePlacements' => $availablePlacements,
        ]);
    }
}
