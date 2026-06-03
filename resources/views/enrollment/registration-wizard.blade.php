<div>
    <x-mary-header :title="__('registration.wizard.title')" :subtitle="__('registration.wizard.subtitle')" separator />

    <ul class="steps steps-vertical lg:steps-horizontal w-full mb-8">
        <li class="step {{ $step >= 1 ? 'step-primary' : '' }}">{{ __('registration.wizard.step_program') }}</li>
        <li class="step {{ $step >= 2 ? 'step-primary' : '' }}">{{ __('registration.wizard.step_placement') }}</li>
        <li class="step {{ $step >= 3 ? 'step-primary' : '' }}">{{ __('registration.wizard.step_finalize') }}</li>
    </ul>

    <x-mary-card>
        @if($step === 1)
            <div class="grid grid-cols-1 gap-4">
                <x-mary-select
                    :label="__('registration.wizard.step_program')"
                    wire:model.live="form.internship_id"
                    :options="$this->internships"
                    :placeholder="__('registration.wizard.step_program')" />

                <x-mary-input :label="__('registration.wizard.label_academic_year')" wire:model="form.academic_year" placeholder="e.g. 2025/2026" />
            </div>
        @elseif($step === 2)
            <div class="grid grid-cols-1 gap-4">
                <x-mary-select
                    :label="__('registration.wizard.step_placement')"
                    wire:model="form.placement_id"
                    :options="$this->placements"
                    :placeholder="__('registration.wizard.step_placement')"
                    :hint="__('registration.wizard.propose_hint')" />

                <div class="divider">{{ __('common.or') }}</div>

                <x-mary-input :label="__('registration.wizard.proposed_company')" wire:model="form.proposed_company_name" />
                <x-mary-textarea :label="__('registration.wizard.proposed_address')" wire:model="form.proposed_company_address" />
            </div>
        @elseif($step === 3)
            <div class="prose max-w-none">
                <h3>{{ __('registration.wizard.review_title') }}</h3>
                <p>{{ __('registration.wizard.review_desc') }}</p>
                <ul>
                    <li><strong>{{ __('registration.wizard.label_program') }}:</strong> {{ $this->internships->find($form->internship_id)?->name }}</li>
                    <li><strong>{{ __('registration.wizard.label_academic_year') }}:</strong> {{ $form->academic_year }}</li>
                    <li><strong>{{ __('registration.wizard.label_placement') }}:</strong> {{ $this->placements->find($form->placement_id)?->name ?? __('registration.wizard.proposed_own') }}</li>
                </ul>
            </div>
        @endif

        <x-slot:actions>
            @if($step > 1)
                <x-mary-button :label="__('registration.wizard.previous')" wire:click="previousStep" />
            @endif

            @if($step < 3)
                <x-mary-button :label="__('registration.wizard.next')" wire:click="nextStep" class="btn-primary" />
            @else
                <x-mary-button :label="__('registration.wizard.submit')" wire:click="submit" icon="o-check" class="btn-primary" />
            @endif
        </x-slot:actions>
    </x-mary-card>
</div>
