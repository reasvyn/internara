<?php

declare(strict_types=1);

namespace App\Enrollment\Livewire;

use App\Enrollment\Actions\RegisterInternshipAction;
use App\Enrollment\Livewire\Forms\RegistrationWizardForm;
use App\Enrollment\Models\Placement;
use App\Enrollment\Models\Registration;
use App\Program\Internship\Enums\InternshipStatus;
use App\Program\Internship\Models\Internship;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Component;

class RegistrationWizard extends Component
{
    use AuthorizesRequests;

    public int $step = 1;

    public RegistrationWizardForm $form;

    public function boot(): void
    {
        $this->authorize('create', Registration::class);
    }

    #[Computed]
    public function internships(): Collection
    {
        return Internship::where('status', InternshipStatus::PUBLISHED->value)->get();
    }

    #[Computed]
    public function placements(): Collection
    {
        if (! $this->form->internship_id) {
            return new Collection;
        }

        return Placement::where('internship_id', $this->form->internship_id)
            ->with('company')
            ->get()
            ->filter(fn ($p) => ! $p->asPlacementCapacity()->isFull())
            ->values();
    }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            $this->validate(['form.internship_id' => 'required']);
        }

        $this->step++;
    }

    public function previousStep(): void
    {
        $this->step--;
    }

    public function submit(RegisterInternshipAction $registerAction): void
    {
        $this->form->validate();

        $registerAction->execute(auth()->user(), [
            'internship_id' => $this->form->internship_id,
            'placement_id' => $this->form->placement_id ?: null,
            'academic_year' => $this->form->academic_year,
            'proposed_company_name' => $this->form->proposed_company_name ?: null,
            'proposed_company_address' => $this->form->proposed_company_address ?: null,
        ]);

        flash()->success(__('registration.wizard.success'));
        $this->redirect('/dashboard');
    }

    public function render(): View
    {
        return view('enrollment.registration-wizard');
    }
}
