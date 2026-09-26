<div class="p-6 sm:p-10 lg:p-12">
    <div class="mb-8">
        <h2 class="text-base-content mb-1 text-2xl font-bold tracking-tight">{{ __('setup.wizard.department') }}</h2>
        <p class="text-base-content/60 text-sm">{{ __('setup.wizard.department_subtitle') }}</p>
    </div>

    {{-- Department Context Banner --}}
    <div class="border-base-content/10 bg-base-200/40 text-base-content/80 mb-8 flex items-start gap-3.5 rounded-2xl border p-4.5 text-xs leading-relaxed shadow-2xs sm:p-5">
        <div class="border-primary/20 bg-primary/10 text-primary mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-xl border">
            <x-ts-icon name="building-library" class="size-4" />
        </div>
        <div>
            <p class="text-base-content text-xs font-bold">{{ __('setup.wizard.department') }}</p>
            <p class="text-base-content/60 mt-0.5">{{ __('setup.wizard.department_desc') }}</p>
        </div>
    </div>

    <div class="space-y-6">
        <x-ts-input
            :label="__('setup.wizard.department_name').' *'"
            placeholder="{{ __('setup.wizard.department_name_placeholder') }}"
            wire:model.live.debounce.500ms="departmentForm.name"
            icon="building-office-2"
            autofocus
        />

        <x-ts-textarea
            :label="__('setup.wizard.department_description')"
            placeholder="{{ __('setup.wizard.department_description_placeholder') }}"
            wire:model.live.debounce.500ms="departmentForm.description"
            rows="4"
        />
    </div>

    <div class="border-base-content/10 mt-10 flex items-center justify-between border-t pt-6">
        <x-ts-button :text="__('setup.wizard.back')" wire:click="prevStep" color="slate" outline sm icon="arrow-left" />
        <x-ts-button
            :text="__('setup.wizard.next_step')"
            icon="arrow-right"
            position="right"
            color="primary"
            sm
            wire:click="nextStep"
            loading="nextStep"
        />
    </div>
</div>
