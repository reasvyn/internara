<div>
    <x-ui::components.page-header
        :title="__('registration.wizard.title')"
        :description="__('registration.wizard.subtitle')"
    />

    <ul class="steps steps-vertical lg:steps-horizontal mb-8 w-full">
        <li class="step {{ $step >= 1 ? 'step-primary' : '' }}">{{ __('registration.wizard.step_program') }}</li>
        <li class="step {{ $step >= 2 ? 'step-primary' : '' }}">{{ __('registration.wizard.step_placement') }}</li>
        <li class="step {{ $step >= 3 ? 'step-primary' : '' }}">{{ __('registration.wizard.step_finalize') }}</li>
    </ul>

    <x-ts-card shadowless>
        @if ($step === 1)
            <div class="grid grid-cols-1 gap-4">
                <x-ts-select.native
                    :label="__('registration.wizard.step_program')"
                    wire:model.live="form.internship_id"
                    :options="[null => __('registration.wizard.step_program')] + ($this->internships)"
                />

                <x-ts-input
                    :label="__('registration.wizard.label_academic_year')"
                    wire:model="form.academic_year"
                    placeholder="e.g. 2025/2026"
                />
            </div>
        @elseif ($step === 2)
            <div class="grid grid-cols-1 gap-4">
                <x-ts-select.native
                    :label="__('registration.wizard.step_placement')"
                    wire:model="form.placement_id"
                    :options="[null => __('registration.wizard.step_placement')] + ($this->placements)"
                    :hint="__('registration.wizard.propose_hint')"
                />

                <div class="divider">{{ __('common.or') }}</div>

                <x-ts-input
                    :label="__('registration.wizard.proposed_company')"
                    wire:model="form.proposed_company_name"
                />
                <x-ts-textarea
                    :label="__('registration.wizard.proposed_address')"
                    wire:model="form.proposed_company_address"
                />
            </div>
        @elseif ($step === 3)
            <div class="prose max-w-none">
                <h3>{{ __('registration.wizard.review_title') }}</h3>
                <p>{{ __('registration.wizard.review_desc') }}</p>
                <ul>
                    <li>
                        <strong>{{ __('registration.wizard.label_program') }}:</strong>
                        {{ $this->internships->find($form->internship_id)?->name }}
                    </li>
                    <li>
                        <strong>{{ __('registration.wizard.label_academic_year') }}:</strong> {{ $form->academic_year }}
                    </li>
                    <li>
                        <strong>{{ __('registration.wizard.label_placement') }}:</strong>
                        {{ $this->placements->find($form->placement_id)?->name ?? __('registration.wizard.proposed_own') }}
                    </li>
                </ul>
            </div>
        @endif

        <div class="mt-6 flex justify-between gap-2">
            @if ($step > 1)
                <x-ts-button :text="__('registration.wizard.previous')" color="white" wire:click="previousStep" />
            @endif

            <div class="flex gap-2">
                @if ($step < 3)
                    <x-ts-button :text="__('registration.wizard.next')" wire:click="nextStep" color="primary" />
                @else
                    <x-ts-button
                        :text="__('registration.wizard.submit')"
                        wire:click="submit"
                        icon="check"
                        color="primary"
                    />
                @endif
            </div>
        </div>
</div>
