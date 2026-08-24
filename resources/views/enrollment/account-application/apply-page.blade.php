<div>
    <x-core::ui.page-header
        :title="__('registration.account_application.title')"
        :description="__('registration.account_application.subtitle')"
    />

    <x-ts-card shadowless>
        <form wire:submit="submit" no-separator>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="mt-4 md:col-span-2">
                    <h2 class="text-lg font-semibold">{{ __('registration.account_application.personal_info') }}</h2>
                    <hr class="my-2" />
                </div>

                <x-ts-input :label="__('registration.account_application.full_name')" wire:model="form.name" required />
                <x-ts-input
                    :label="__('registration.account_application.email')"
                    wire:model="form.email"
                    type="email"
                    required
                />
                <x-ts-input :label="__('registration.account_application.phone')" wire:model="form.phone" />
                <x-ts-textarea
                    :label="__('registration.account_application.address')"
                    wire:model="form.address"
                    class="md:col-span-2"
                />

                <div class="mt-4 md:col-span-2">
                    <h2 class="text-lg font-semibold">{{ __('registration.account_application.student_info') }}</h2>
                    <hr class="my-2" />
                </div>

                <x-ts-input
                    :label="__('registration.account_application.national_id')"
                    wire:model="form.national_id_number"
                    :placeholder="__('registration.account_application.national_id')"
                />
                <x-ts-input
                    :label="__('registration.account_application.student_id')"
                    wire:model="form.student_id_number"
                    :placeholder="__('registration.account_application.student_id')"
                />
                <x-ts-input
                    :label="__('registration.account_application.class')"
                    wire:model="form.class_name"
                    placeholder="e.g. XII-RPL-1"
                />
                <x-ts-input
                    :label="__('registration.account_application.entry_year')"
                    wire:model="form.entry_year"
                    placeholder="e.g. 2024"
                />

                <div class="mt-4 md:col-span-2">
                    <h2 class="text-lg font-semibold">
                        {{ __('registration.account_application.internship_registration') }}
                    </h2>
                    <hr class="my-2" />
                </div>

                <x-ts-select.native
                    :label="__('registration.wizard.step_program')"
                    wire:model.live="form.internship_id"
                    :options="[null => __('registration.account_application.select_program')] + ($this->internships)"
                    required
                    class="md:col-span-2"
                />
                <x-ts-input
                    :label="__('registration.wizard.label_academic_year')"
                    wire:model="form.academic_year"
                    placeholder="e.g. 2025/2026"
                    required
                />

                <div class="md:col-span-2">
                    <label class="text-sm font-medium">{{ __('registration.account_application.placement_option') }}</label>
                    <div class="mt-1 flex gap-6">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="radio" wire:model.live="form.use_placement" :value="true" />
                            <span>{{ __('registration.account_application.choose_placement') }}</span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="radio" wire:model.live="form.use_placement" :value="false" />
                            <span>{{ __('registration.account_application.propose_company') }}</span>
                        </label>
                    </div>
                </div>

                @if ($form->use_placement)
                    <x-ts-select.native
                        :label="__('registration.account_application.available_placement')"
                        wire:model="form.placement_id"
                        :options="[null => __('registration.account_application.select_placement')] + ($this->placements)"
                        class="md:col-span-2"
                    />
                @else
                    <x-ts-input
                        :label="__('registration.account_application.proposed_company')"
                        wire:model="form.proposed_company_name"
                        class="md:col-span-2"
                    />
                    <x-ts-textarea
                        :label="__('registration.account_application.proposed_address')"
                        wire:model="form.proposed_company_address"
                        class="md:col-span-2"
                    />
                @endif
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <x-ts-button
                    :text="__('registration.account_application.submit')"
                    type="submit"
                    icon="paper-airplane"
                    color="primary"
                />
            </div>
        </form>
</div>
