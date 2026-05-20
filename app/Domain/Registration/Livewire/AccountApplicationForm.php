<?php

declare(strict_types=1);

namespace App\Domain\Registration\Livewire;

use App\Domain\Internship\Models\Internship;
use App\Domain\Internship\Rules\OpenForRegistration;
use App\Domain\Placement\Models\Placement;
use App\Domain\Registration\Actions\ApplyAccountAction;
use App\Domain\School\Models\School;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AccountApplicationForm extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $address = '';

    public string $national_identifier = '';

    public string $registration_number = '';

    public string $school_id = '';

    public string $department_id = '';

    public string $class_name = '';

    public string $entry_year = '';

    public string $internship_id = '';

    public string $placement_id = '';

    public string $academic_year = '';

    public string $proposed_company_name = '';

    public string $proposed_company_address = '';

    public bool $use_placement = true;

    #[Computed]
    public function internships(): Collection
    {
        return Internship::whereIn('status', ['published', 'active'])->get();
    }

    #[Computed]
    public function placements(): Collection
    {
        if (! $this->internship_id) {
            return new Collection;
        }

        return Placement::where('internship_id', $this->internship_id)
            ->with('company')
            ->get()
            ->filter(fn ($p) => ! $p->asPlacementCapacity()->isFull());
    }

    #[Computed]
    public function schools(): Collection
    {
        return School::all();
    }

    public function submit(ApplyAccountAction $action): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:account_applications,email|unique:users,email',
            'internship_id' => ['required', 'exists:internships,id', new OpenForRegistration],
            'academic_year' => 'required|string|max:20',
        ];

        if ($this->use_placement) {
            $rules['placement_id'] = 'required|exists:internship_placements,id';
        } else {
            $rules['proposed_company_name'] = 'required|string|max:255';
            $rules['proposed_company_address'] = 'required|string|max:1000';
        }

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'national_identifier' => $this->national_identifier,
            'registration_number' => $this->registration_number,
            'school_id' => $this->school_id ?: null,
            'department_id' => $this->department_id ?: null,
            'class_name' => $this->class_name,
            'entry_year' => $this->entry_year ? (int) $this->entry_year : null,
            'internship_id' => $this->internship_id,
            'placement_id' => $this->use_placement ? $this->placement_id : null,
            'academic_year' => $this->academic_year,
            'proposed_company_name' => $this->use_placement ? null : $this->proposed_company_name,
            'proposed_company_address' => $this->use_placement ? null : $this->proposed_company_address,
        ];

        $action->execute($data);

        flash()->success(__('internship.account_application.success'));
        $this->reset();
    }

    public function render(): View
    {
        return <<<'HTML'
        <div>
            <x-mary-header :title="__('internship.account_application.title')" :subtitle="__('internship.account_application.subtitle')" separator />

            <x-mary-card>
                <x-mary-form wire:submit="submit" no-separator>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2 mt-4">
                            <h2 class="text-lg font-semibold">{{ __('internship.account_application.personal_info') }}</h2>
                            <hr class="my-2" />
                        </div>

                        <x-mary-input :label="__('internship.account_application.full_name')" wire:model="name" required />
                        <x-mary-input :label="__('internship.account_application.email')" wire:model="email" type="email" required />
                        <x-mary-input :label="__('internship.account_application.phone')" wire:model="phone" />
                        <x-mary-textarea :label="__('internship.account_application.address')" wire:model="address" class="md:col-span-2" />

                        <div class="md:col-span-2 mt-4">
                            <h2 class="text-lg font-semibold">{{ __('internship.account_application.school_info') }}</h2>
                            <hr class="my-2" />
                        </div>

                        <x-mary-select :label="__('internship.account_application.school')" wire:model.live="school_id" :options="$this->schools" :placeholder="__('internship.account_application.select_school')" />
                        <x-mary-input :label="__('internship.account_application.nisn')" wire:model="national_identifier" placeholder="National Student ID" />
                        <x-mary-input :label="__('internship.account_application.nis')" wire:model="registration_number" placeholder="School Student ID" />
                        <x-mary-input :label="__('internship.account_application.class')" wire:model="class_name" placeholder="e.g. XII-RPL-1" />
                        <x-mary-input :label="__('internship.account_application.entry_year')" wire:model="entry_year" placeholder="e.g. 2024" />

                        <div class="md:col-span-2 mt-4">
                            <h2 class="text-lg font-semibold">{{ __('internship.account_application.internship_registration') }}</h2>
                            <hr class="my-2" />
                        </div>

                        <x-mary-select :label="__('internship.registration_wizard.step_program')" wire:model.live="internship_id" :options="$this->internships" :placeholder="__('internship.account_application.select_program')" required class="md:col-span-2" />
                        <x-mary-input :label="__('internship.registration_wizard.label_academic_year')" wire:model="academic_year" placeholder="e.g. 2025/2026" required />

                        <div class="md:col-span-2">
                            <label class="font-medium text-sm">{{ __('internship.account_application.placement_option') }}</label>
                            <div class="flex gap-6 mt-1">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" wire:model="use_placement" :value="true" />
                                    <span>{{ __('internship.account_application.choose_placement') }}</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" wire:model="use_placement" :value="false" />
                                    <span>{{ __('internship.account_application.propose_company') }}</span>
                                </label>
                            </div>
                        </div>

                        @if($use_placement)
                            <x-mary-select :label="__('internship.account_application.available_placement')" wire:model="placement_id" :options="$this->placements" :placeholder="__('internship.account_application.select_placement')" class="md:col-span-2" />
                        @else
                            <x-mary-input :label="__('internship.account_application.proposed_company')" wire:model="proposed_company_name" class="md:col-span-2" />
                            <x-mary-textarea :label="__('internship.account_application.proposed_address')" wire:model="proposed_company_address" class="md:col-span-2" />
                        @endif
                    </div>

                    <x-slot:actions>
                        <x-mary-button :label="__('internship.account_application.submit')" type="submit" icon="o-paper-airplane" class="btn-primary" />
                    </x-slot:actions>
                </x-mary-form>
            </x-mary-card>
        </div>
        HTML;
    }
}
