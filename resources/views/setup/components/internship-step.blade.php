<div class="p-6 sm:p-8">
    <div class="mb-8">
        <h2 class="text-xl font-bold mb-1">{{ __('setup.wizard.internship') }}</h2>
        <p class="text-sm text-base-content/50">{{ __('setup.wizard.internship_subtitle') }}</p>
    </div>

    <div class="space-y-5">
        <x-mary-input
            label="{{ __('setup.wizard.program_name') }}"
            placeholder="{{ __('setup.wizard.program_name_placeholder') }}"
            wire:model.live.debounce.500ms="internshipForm.name"
        />

        <x-mary-textarea
            label="{{ __('setup.wizard.program_description') }}"
            placeholder="{{ __('setup.wizard.program_description_placeholder') }}"
            wire:model.live.debounce.500ms="internshipForm.description"
            rows="3"
        />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <x-mary-input
                label="{{ __('setup.wizard.start_date') }}"
                type="date"
                wire:model.live.debounce.500ms="internshipForm.start_date"
            />

            <x-mary-input
                label="{{ __('setup.wizard.end_date') }}"
                type="date"
                wire:model.live.debounce.500ms="internshipForm.end_date"
            />
        </div>
    </div>

    <div class="flex items-center justify-between pt-6 mt-8 border-t border-base-content/10">
        <x-mary-button
            label="{{ __('setup.wizard.back') }}"
            wire:click="prevStep"
            class="btn-ghost btn-sm"
        />
        <x-mary-button
            label="{{ __('setup.wizard.next_step') }}"
            icon="o-arrow-right"
            class="btn-primary btn-sm"
            wire:click="nextStep"
            spinner="nextStep"
        />
    </div>
</div>
