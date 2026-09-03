<div class="p-6 sm:p-8">
    <div class="mb-8">
        <h2 class="mb-1 text-xl font-bold">{{ __('setup.wizard.department') }}</h2>
        <p class="text-base-content/50 text-sm">{{ __('setup.wizard.department_subtitle') }}</p>
    </div>

    <div class="bg-base-200/40 text-base-content/70 mb-6 rounded-lg px-5 py-4 text-sm leading-relaxed">
        {{ __('setup.wizard.department_desc') }}
    </div>

    <div class="space-y-5">
        <x-ts-input
            label="{{ __('setup.wizard.department_name') }}"
            placeholder="{{ __('setup.wizard.department_name_placeholder') }}"
            wire:model.live.debounce.500ms="departmentForm.name"
            icon="building-library"
        />

        <x-ts-textarea
            label="{{ __('setup.wizard.department_description') }}"
            placeholder="{{ __('setup.wizard.department_description_placeholder') }}"
            wire:model.live.debounce.500ms="departmentForm.description"
            rows="3"
        />
    </div>

    <div class="border-base-content/10 mt-8 flex items-center justify-between border-t pt-6">
        <x-ts-button text="{{ __('setup.wizard.back') }}" wire:click="prevStep" color="slate" outline sm />
        <x-ts-button
            text="{{ __('setup.wizard.next_step') }}"
            icon-right="arrow-right"
            color="primary"
            sm
            wire:click="nextStep"
            loading="nextStep"
        />
    </div>
</div>
