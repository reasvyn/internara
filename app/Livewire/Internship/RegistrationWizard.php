<?php

declare(strict_types=1);

namespace App\Livewire\Internship;

use App\Domain\Internship\Actions\RegisterInternshipAction;
use App\Domain\Internship\Models\Internship;
use App\Domain\Internship\Models\Placement;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Mary\Traits\Toast;

class RegistrationWizard extends Component
{
    use Toast;

    public int $step = 1;

    public array $data = [
        'internship_id' => '',
        'placement_id' => '',
        'academic_year' => '',
        'proposed_company_name' => '',
        'proposed_company_address' => '',
    ];

    /**
     * Get available internship programs.
     */
    #[Computed]
    public function internships(): Collection
    {
        return Internship::where('status', 'active')->get();
    }

    /**
     * Get available placements for the selected internship.
     */
    #[Computed]
    public function placements(): Collection
    {
        if (! $this->data['internship_id']) {
            return new Collection;
        }

        return Placement::where('internship_id', $this->data['internship_id'])
            ->with('company')
            ->get()
            ->filter(fn ($p) => ! $p->isFull());
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
            'data.internship_id' => 'required',
            'data.academic_year' => 'required',
        ]);

        $registerAction->execute(auth()->user(), $this->data);

        $this->success('Registration submitted successfully.');
        $this->redirect('/dashboard');
    }

    public function render()
    {
        return <<<'HTML'
        <div>
            <x-mary-header title="Internship Registration" subtitle="Register for your upcoming internship program" separator />

            <ul class="steps steps-vertical lg:steps-horizontal w-full mb-8">
                <li class="step {{ $step >= 1 ? 'step-primary' : '' }}">Program Selection</li>
                <li class="step {{ $step >= 2 ? 'step-primary' : '' }}">Placement Choice</li>
                <li class="step {{ $step >= 3 ? 'step-primary' : '' }}">Finalize</li>
            </ul>

            <x-mary-card>
                @if($step === 1)
                    <div class="grid grid-cols-1 gap-4">
                        <x-mary-select
                            label="Select Internship Program"
                            wire:model.live="data.internship_id"
                            :options="$this->internships"
                            placeholder="Choose a program" />

                        <x-mary-input label="Academic Year" wire:model="data.academic_year" placeholder="e.g. 2025/2026" />
                    </div>
                @elseif($step === 2)
                    <div class="grid grid-cols-1 gap-4">
                        <x-mary-select
                            label="Industry Partner (Optional)"
                            wire:model="data.placement_id"
                            :options="$this->placements"
                            placeholder="Choose a placement"
                            hint="Leave empty if you want to propose your own company" />

                        <div class="divider">OR</div>

                        <x-mary-input label="Proposed Company Name" wire:model="data.proposed_company_name" />
                        <x-mary-textarea label="Proposed Company Address" wire:model="data.proposed_company_address" />
                    </div>
                @elseif($step === 3)
                    <div class="prose max-w-none">
                        <h3>Review Your Registration</h3>
                        <p>Please confirm that all information provided is correct before submitting.</p>
                        <ul>
                            <li><strong>Program:</strong> {{ $this->internships->find($data['internship_id'])?->name }}</li>
                            <li><strong>Academic Year:</strong> {{ $data['academic_year'] }}</li>
                            <li><strong>Placement:</strong> {{ $this->placements->find($data['placement_id'])?->name ?? 'Proposed Own Company' }}</li>
                        </ul>
                    </div>
                @endif

                <x-slot:actions>
                    @if($step > 1)
                        <x-mary-button label="Previous" wire:click="previousStep" />
                    @endif

                    @if($step < 3)
                        <x-mary-button label="Next" wire:click="nextStep" class="btn-primary" />
                    @else
                        <x-mary-button label="Submit Registration" wire:click="submit" icon="o-check" class="btn-primary" />
                    @endif
                </x-slot:actions>
            </x-mary-card>
        </div>
        HTML;
    }
}
