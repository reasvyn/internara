<?php

declare(strict_types=1);

namespace App\Enrollment\Registration\Livewire;

use App\Enrollment\Placement\Models\Placement;
use App\Enrollment\Registration\Actions\VerifyRegistrationAction;
use App\Enrollment\Registration\Models\Registration;
use App\User\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Component;

class RegistrationVerification extends Component
{
    use AuthorizesRequests;

    public ?string $processId = null;

    public function boot(): void
    {
        $this->authorize('viewAny', Registration::class);
    }

    public string $placement_id = '';

    public array $mentor_ids = [];

    public bool $showProcessModal = false;

    #[Computed]
    public function pendingRegistrations(): Collection
    {
        return Registration::with(['student', 'internship', 'documents'])
            ->where('placement_id', null)
            ->currentStatus('pending')
            ->latest()
            ->get();
    }

    #[Computed]
    public function selectedRegistration(): ?Registration
    {
        if ($this->processId === null) {
            return null;
        }

        return Registration::with('internship')->find($this->processId);
    }

    #[Computed]
    public function availablePlacements(): Collection
    {
        $registration = $this->selectedRegistration;
        if ($registration === null) {
            return new Collection;
        }

        return Placement::with('company')
            ->where('internship_id', $registration->internship_id)
            ->get()
            ->filter(fn ($p) => ! $p->asPlacementCapacity()->isFull())
            ->values();
    }

    #[Computed]
    public function mentors(): Collection
    {
        return User::role(['teacher', 'supervisor'])->get();
    }

    public function process(string $id): void
    {
        $this->resetErrorBag();
        $this->reset(['placement_id', 'mentor_ids']);

        $registration = Registration::with('internship')->findOrFail($id);

        $this->authorize('update', $registration);

        $this->processId = $id;
        $this->showProcessModal = true;
    }

    public function confirmProcess(VerifyRegistrationAction $action): void
    {
        $this->validate([
            'placement_id' => 'required|exists:placements,id',
            'mentor_ids' => 'nullable|array',
            'mentor_ids.*' => ['exists:users,id'],
        ]);

        try {
            $action->execute($this->processId, [
                'placement_id' => $this->placement_id,
                'mentor_ids' => $this->mentor_ids,
            ]);

            flash()->success(__('registration.verification.success'));
            $this->showProcessModal = false;
            $this->processId = null;
            $this->placement_id = '';
            $this->mentor_ids = [];
        } catch (\RuntimeException $e) {
            flash()->error($e->getMessage());
        }
    }

    public function render(): View
    {
        return view('enrollment.registration.registration-verification');
    }
}
