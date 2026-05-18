<?php

declare(strict_types=1);

namespace App\Livewire\Internship;

use App\Actions\Internship\RequestPlacementChangeAction;
use App\Models\Placement;
use App\Models\PlacementChangeRequest;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;

class StudentPlacementChangeRequest extends Component
{
    public ?string $registrationId = null;

    public string $toPlacementId = '';

    public string $reason = '';

    public ?PlacementChangeRequest $pendingRequest = null;

    public function boot(): void
    {
        abort_unless(auth()->user()->hasRole('student'), 403);
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
            $this->pendingRequest = PlacementChangeRequest::where('registration_id', $registration->id)
                ->where('status', 'pending')
                ->first();
        }
    }

    public function submit(RequestPlacementChangeAction $action): void
    {
        $this->validate([
            'toPlacementId' => ['required', 'exists:internship_placements,id'],
            'reason' => ['required', 'string', 'min:20', 'max:2000'],
        ]);

        $registration = Registration::findOrFail($this->registrationId);

        $action->execute($registration, [
            'to_placement_id' => $this->toPlacementId,
            'reason' => $this->reason,
            'requested_by' => auth()->id(),
        ]);

        flash()->success(__('placement_change.request_success'));
        $this->pendingRequest = PlacementChangeRequest::where('registration_id', $registration->id)
            ->where('status', 'pending')
            ->first();
        $this->reset('toPlacementId', 'reason');
    }

    #[Layout('layouts::app')]
    public function render()
    {
        $registration = $this->registrationId
            ? Registration::with('placement.company', 'internship.placements.company')->find($this->registrationId)
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

        return view('livewire.internship.student-placement-change-request', [
            'registration' => $registration,
            'availablePlacements' => $availablePlacements,
        ]);
    }
}
