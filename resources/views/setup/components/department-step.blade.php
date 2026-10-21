<div class="p-4.5 sm:p-6 lg:p-7">
    <div class="mb-4 sm:mb-5">
        <h2 class="text-base-content mb-1 text-lg font-bold tracking-tight sm:text-2xl">
            {{ __('setup.wizard.department') }}
        </h2>
        <p class="text-base-content/60 text-xs sm:text-sm">{{ __('setup.wizard.department_subtitle') }}</p>
    </div>

    {{-- Department Context Banner --}}
    <div class="border-base-content/10 bg-base-200/40 text-base-content/80 mb-4 flex items-start gap-3 rounded-2xl border p-3 text-xs leading-relaxed shadow-2xs sm:mb-5 sm:p-3.5">
        <div class="border-primary/20 bg-primary/10 text-primary mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-xl border sm:size-8">
            <x-ts-icon name="building-library" class="size-4" />
        </div>
        <div>
            <p class="text-base-content text-xs font-bold">{{ __('setup.wizard.department') }}</p>
            <p class="text-base-content/60 mt-0.5 text-[11px] sm:text-xs">{{ __('setup.wizard.department_desc') }}</p>
        </div>
    </div>

    <div class="space-y-4 sm:space-y-4.5">
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
            rows="2"
        />
    </div>

    <div class="border-base-content/10 mt-6 flex items-center justify-between border-t pt-4 sm:mt-8 sm:pt-5">
        <x-ts-button
            :text="__('setup.wizard.back')"
            icon="arrow-left"
            color="slate"
            outline
            wire:click="prevStep"
            loading="prevStep"
        />
        <x-ts-button
            :text="__('setup.wizard.next_step')"
            icon="arrow-right"
            position="right"
            color="primary"
            wire:click="nextStep"
            loading="nextStep"
        />
    </div>
</div>
