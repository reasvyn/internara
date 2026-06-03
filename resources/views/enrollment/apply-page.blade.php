<div>
    <x-mary-header :title="__('registration.account_application.title')" :subtitle="__('registration.account_application.subtitle')" separator />

    <x-mary-card>
        <x-mary-form wire:submit="submit" no-separator>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2 mt-4">
                    <h2 class="text-lg font-semibold">{{ __('registration.account_application.personal_info') }}</h2>
                    <hr class="my-2" />
                </div>

                <x-mary-input :label="__('registration.account_application.full_name')" wire:model="form.name" required />
                <x-mary-input :label="__('registration.account_application.email')" wire:model="form.email" type="email" required />
                <x-mary-input :label="__('registration.account_application.phone')" wire:model="form.phone" />
                <x-mary-textarea :label="__('registration.account_application.address')" wire:model="form.address" class="md:col-span-2" />

                <div class="md:col-span-2 mt-4">
                    <h2 class="text-lg font-semibold">{{ __('registration.account_application.school_info') }}</h2>
                    <hr class="my-2" />
                </div>

                <x-mary-select :label="__('registration.account_application.school')" wire:model.live="form.school_id" :options="$this->schools" :placeholder="__('registration.account_application.select_school')" />
                <x-mary-input :label="__('registration.account_application.national_id')" wire:model="form.national_id_number" placeholder="National ID Number" />
                <x-mary-input :label="__('registration.account_application.student_id')" wire:model="form.student_id_number" placeholder="Student ID Number" />
                <x-mary-input :label="__('registration.account_application.class')" wire:model="form.class_name" placeholder="e.g. XII-RPL-1" />
                <x-mary-input :label="__('registration.account_application.entry_year')" wire:model="form.entry_year" placeholder="e.g. 2024" />

                <div class="md:col-span-2 mt-4">
                    <h2 class="text-lg font-semibold">{{ __('registration.account_application.internship_registration') }}</h2>
                    <hr class="my-2" />
                </div>

                <x-mary-select :label="__('registration.wizard.step_program')" wire:model.live="form.internship_id" :options="$this->internships" :placeholder="__('registration.account_application.select_program')" required class="md:col-span-2" />
                <x-mary-input :label="__('registration.wizard.label_academic_year')" wire:model="form.academic_year" placeholder="e.g. 2025/2026" required />

                <div class="md:col-span-2">
                    <label class="font-medium text-sm">{{ __('registration.account_application.placement_option') }}</label>
                    <div class="flex gap-6 mt-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" wire:model.live="form.use_placement" :value="true" />
                            <span>{{ __('registration.account_application.choose_placement') }}</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" wire:model.live="form.use_placement" :value="false" />
                            <span>{{ __('registration.account_application.propose_company') }}</span>
                        </label>
                    </div>
                </div>

                @if($form->use_placement)
                    <x-mary-select :label="__('registration.account_application.available_placement')" wire:model="form.placement_id" :options="$this->placements" :placeholder="__('registration.account_application.select_placement')" class="md:col-span-2" />
                @else
                    <x-mary-input :label="__('registration.account_application.proposed_company')" wire:model="form.proposed_company_name" class="md:col-span-2" />
                    <x-mary-textarea :label="__('registration.account_application.proposed_address')" wire:model="form.proposed_company_address" class="md:col-span-2" />
                @endif
            </div>

            <x-slot:actions>
                <x-mary-button :label="__('registration.account_application.submit')" type="submit" icon="o-paper-airplane" class="btn-primary" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-card>
</div>
