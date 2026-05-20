<?php

declare(strict_types=1);

namespace App\Domain\Registration\Livewire;

use App\Domain\Internship\Actions\RegisterInternshipAction;
use App\Domain\Internship\Models\Internship;
use App\Domain\Internship\Rules\OpenForRegistration;
use App\Domain\Placement\Models\Placement;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class RegistrationWizard extends Component
{
    public int $step = 1;

    public array $data = [
        'internship_id' => '',
        'placement_id' => '',
        'academic_year' => '',
        'proposed_company_name' => '',
        'proposed_company_address' => '',
    ];

    #[Computed]
    public function internships(): Collection
    {
        return Internship::where('status', 'active')->get();
    }

    #[Computed]
    public function placements(): Collection
    {
        if (! $this->data['internship_id']) {
            return new Collection;
        }

        return Placement::where('internship_id', $this->data['internship_id'])
            ->with('company')
            ->get()
            ->filter(fn ($p) => ! $p->asPlacementCapacity()->isFull());
    }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            $this->validate(['data.internship_id' => 'required']);
        }

        $this->step++;
    }

    public function previousStep(): void
    {
        $this->step--;
    }

    public function submit(RegisterInternshipAction $registerAction): void
    {
        $this->validate([
            'data.internship_id' => ['required', new OpenForRegistration],
            'data.academic_year' => 'required',
        ]);

        $registerAction->execute(auth()->user(), $this->data);

        flash()->success(__('internship.registration_wizard.success'));
        $this->redirect('/dashboard');
    }

    public function render(): View
    {
        return <<<'HTML'
        <div>
            <x-mary-header :title="__('internship.registration_wizard.title')" :subtitle="__('internship.registration_wizard.subtitle')" separator />

            <ul class="steps steps-vertical lg:steps-horizontal w-full mb-8">
                <li class="step {{ $step >= 1 ? 'step-primary' : '' }}">{{ __('internship.registration_wizard.step_program') }}</li>
                <li class="step {{ $step >= 2 ? 'step-primary' : '' }}">{{ __('internship.registration_wizard.step_placement') }}</li>
                <li class="step {{ $step >= 3 ? 'step-primary' : '' }}">{{ __('internship.registration_wizard.step_finalize') }}</li>
            </ul>

            <x-mary-card>
                @if($step === 1)
                    <div class="grid grid-cols-1 gap-4">
                        <x-mary-select
                            :label="__('internship.registration_wizard.step_program')"
                            wire:model.live="data.internship_id"
                            :options="$this->internships"
                            :placeholder="__('internship.registration_wizard.step_program')" />

                        <x-mary-input :label="__('internship.registration_wizard.label_academic_year')" wire:model="data.academic_year" placeholder="e.g. 2025/2026" />
                    </div>
                @elseif($step === 2)
                    <div class="grid grid-cols-1 gap-4">
                        <x-mary-select
                            :label="__('internship.registration_wizard.step_placement')"
                            wire:model="data.placement_id"
                            :options="$this->placements"
                            :placeholder="__('internship.registration_wizard.step_placement')"
                            :hint="__('internship.registration_wizard.propose_hint')" />

                        <div class="divider">{{ __('common.or') }}</div>

                        <x-mary-input :label="__('internship.registration_wizard.proposed_company')" wire:model="data.proposed_company_name" />
                        <x-mary-textarea :label="__('internship.registration_wizard.proposed_address')" wire:model="data.proposed_company_address" />
                    </div>
                @elseif($step === 3)
                    <div class="prose max-w-none">
                        <h3>{{ __('internship.registration_wizard.review_title') }}</h3>
                        <p>{{ __('internship.registration_wizard.review_desc') }}</p>
                        <ul>
                            <li><strong>{{ __('internship.registration_wizard.label_program') }}:</strong> {{ $this->internships->find($data['internship_id'])?->name }}</li>
                            <li><strong>{{ __('internship.registration_wizard.label_academic_year') }}:</strong> {{ $data['academic_year'] }}</li>
                            <li><strong>{{ __('internship.registration_wizard.label_placement') }}:</strong> {{ $this->placements->find($data['placement_id'])?->name ?? __('internship.registration_wizard.proposed_own') }}</li>
                        </ul>
                    </div>
                @endif

                <x-slot:actions>
                    @if($step > 1)
                        <x-mary-button :label="__('internship.registration_wizard.previous')" wire:click="previousStep" />
                    @endif

                    @if($step < 3)
                        <x-mary-button :label="__('internship.registration_wizard.next')" wire:click="nextStep" class="btn-primary" />
                    @else
                        <x-mary-button :label="__('internship.registration_wizard.submit')" wire:click="submit" icon="o-check" class="btn-primary" />
                    @endif
                </x-slot:actions>
            </x-mary-card>
        </div>
        HTML;
    }
}
