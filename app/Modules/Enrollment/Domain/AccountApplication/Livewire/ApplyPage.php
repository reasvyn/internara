<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\AccountApplication\Livewire;

use App\Modules\Enrollment\Domain\AccountApplication\Actions\ApplyAccountAction;
use App\Modules\Enrollment\Domain\AccountApplication\Livewire\Forms\AccountApplicationForm;
use App\Modules\Enrollment\Domain\AccountApplication\Models\AccountApplication;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\UI\Livewire\BaseFormView;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use TallStackUi\Traits\Interactions;

#[Layout('ui::layouts.guest')]
class ApplyPage extends BaseFormView
{
    use Interactions;

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
        return Internship::query()
            ->whereIn('status', ['published', 'active'])
            ->where(function ($q) {
                $q->whereNull('registration_start_date')->orWhere(
                    'registration_start_date',
                    '<=',
                    now()->toDateString(),
                );
            })
            ->where(function ($q) {
                $q->whereNull('registration_end_date')->orWhere(
                    'registration_end_date',
                    '>=',
                    now()->toDateString(),
                );
            })
            ->get();
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
        if ($this->internships->isEmpty()) {
            $this->toast()->error(__('registration.account_application.closed_desc'))->send();

            return;
        }

        $this->authorize('create', AccountApplication::class);

        $this->form->validate();

        $this->handleSave(function () use ($action) {
            $action->execute($this->form->toArray());
            $this->toast()->success(__('registration.account_application.success'))->send();
            $this->form->reset();
        });
    }

    public function render(): View
    {
        return view('enrollment.account-application.apply-page')->title(__('registration.account_application.title'));
    }
}
