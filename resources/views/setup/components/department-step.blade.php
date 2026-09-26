<div class="p-6 sm:p-10">
    <div class="mb-8">
        <h2 class="text-base-content mb-1 text-xl font-bold tracking-tight">{{ __('setup.wizard.department') }}</h2>
        <p class="text-base-content/60 text-sm">{{ __('setup.wizard.department_subtitle') }}</p>
    </div>

    <div class="border-base-content/10 bg-base-200/40 text-base-content/80 mb-6 flex items-start gap-3 rounded-xl border p-4 text-sm leading-relaxed sm:p-5">
        <x-ts-icon name="information-circle" class="text-primary mt-0.5 size-5 shrink-0" />
        <p>{{ __('setup.wizard.department_desc') }}</p>
    </div>

    <div class="space-y-5">
        <x-ts-input
            :label="__('setup.wizard.department_name').' *'"
            placeholder="{{ __('setup.wizard.department_name_placeholder') }}"
            wire:model.live.debounce.500ms="departmentForm.name"
            icon="building-library"
            autofocus
        />

        <x-ts-textarea
            :label="__('setup.wizard.department_description')"
            placeholder="{{ __('setup.wizard.department_description_placeholder') }}"
            wire:model.live.debounce.500ms="departmentForm.description"
            rows="3"
        />
    </div>

    <div class="border-base-content/10 mt-10 flex items-center justify-between border-t pt-6">
        <x-ts-button :text="__('setup.wizard.back')" wire:click="prevStep" color="slate" outline sm icon="arrow-left" />
        <x-ts-button
            :text="__('setup.wizard.next_step')"
            icon-right="arrow-right"
            color="primary"
            sm
            wire:click="nextStep"
            loading="nextStep"
        />
    </div>
</div>
