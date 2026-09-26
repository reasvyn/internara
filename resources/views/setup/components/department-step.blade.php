<div class="p-6 sm:p-10">
    <div class="mb-8">
        <h2 class="text-base-content mb-1 text-2xl font-bold tracking-tight">{{ __('setup.wizard.department') }}</h2>
        <p class="text-base-content/60 text-sm">{{ __('setup.wizard.department_subtitle') }}</p>
    </div>

    <div class="border-base-content/10 bg-base-200/50 text-base-content/80 mb-6 flex items-start gap-3.5 rounded-2xl border p-4 text-sm leading-relaxed shadow-2xs sm:p-5">
        <div class="border-primary/20 bg-primary/10 text-primary mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg border">
            <x-ts-icon name="information-circle" class="size-4" />
        </div>
        <p class="pt-0.5">{{ __('setup.wizard.department_desc') }}</p>
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
