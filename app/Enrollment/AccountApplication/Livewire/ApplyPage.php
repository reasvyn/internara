<?php

declare(strict_types=1);

namespace App\Enrollment\AccountApplication\Livewire;

use App\Enrollment\AccountApplication\Actions\ApplyAccountAction;
use App\Enrollment\AccountApplication\Livewire\Forms\AccountApplicationForm;
use App\Enrollment\Placement;
use App\Program\Internship\Models\Internship;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ApplyPage extends Component
{
    public AccountApplicationForm $form;

    public function updatedFormUsePlacement(): void
    {
        $this->form->placement_id = '';
        $this->form->proposed_company_name = '';
        $this->form->proposed_company_address = '';
    }

    #[Computed]
    public function internships(): Collection
    {
        return Internship::whereIn('status', ['published', 'active'])->get();
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
            ->filter(fn ($p) => ! $p->asPlacementCapacity()->isFull());
    }

    public function submit(ApplyAccountAction $action): void
    {
        $this->form->validate();

        $action->execute($this->form->toArray());

        flash()->success(__('registration.account_application.success'));
        $this->form->reset();
    }

    public function render(): View
    {
        return view('enrollment.account-application.apply-page');
    }
}
