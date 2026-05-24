<div class="p-6 sm:p-8">
    <div class="mb-8">
        <h2 class="text-xl font-bold mb-1">{{ __('setup.wizard.department') }}</h2>
        <p class="text-sm text-base-content/50">{{ __('setup.wizard.department_subtitle') }}</p>
    </div>

    <div class="bg-base-200/40 rounded-lg px-5 py-4 mb-6 text-sm text-base-content/70 leading-relaxed">
        {{ __('setup.wizard.department_desc') }}
    </div>

    <div class="space-y-5">
        <x-mary-input
            label="{{ __('setup.wizard.department_name') }}"
            placeholder="{{ __('setup.wizard.department_name_placeholder') }}"
            wire:model.live.debounce.500ms="departmentForm.name"
        />

        <x-mary-textarea
            label="{{ __('setup.wizard.department_description') }}"
            placeholder="{{ __('setup.wizard.department_description_placeholder') }}"
            wire:model.live.debounce.500ms="departmentForm.description"
            rows="3"
        />
    </div>

    <div class="flex items-center justify-between pt-6 mt-8 border-t border-base-content/10">
        <x-mary-button
            label="{{ __('setup.wizard.back') }}"
            wire:click="prevStep"
            class="btn-ghost btn-sm"
        />
        <x-mary-button
            label="{{ __('setup.wizard.next_step') }}"
            icon-right="o-arrow-right"
            class="btn-primary btn-sm"
            wire:click="nextStep"
            spinner="nextStep"
        />
    </div>
</div>
