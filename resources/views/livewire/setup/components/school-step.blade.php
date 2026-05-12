<div class="p-6 sm:p-8">
    <div class="mb-8">
        <h2 class="text-xl font-bold mb-1">{{ __('setup.wizard.school_info') }}</h2>
        <p class="text-sm text-base-content/50">{{ __('setup.wizard.school_subtitle') }}</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div class="md:col-span-2">
            <x-mary-input
                label="{{ __('setup.wizard.school_name') }}"
                placeholder="{{ __('setup.wizard.school_name_placeholder') }}"
                wire:model.live="schoolName"
            />
        </div>

        <x-mary-input
            label="{{ __('setup.wizard.school_code') }}"
            placeholder="{{ __('setup.wizard.school_code_placeholder') }}"
            wire:model.live="institutionalCode"
        />

        <x-mary-input
            label="{{ __('setup.wizard.school_email') }}"
            type="email"
            placeholder="{{ __('setup.wizard.school_email_placeholder') }}"
            wire:model.live="schoolEmail"
        />

        <x-mary-input
            label="{{ __('setup.wizard.school_phone') }}"
            placeholder="{{ __('setup.wizard.school_phone_placeholder') }}"
            wire:model.live="schoolPhone"
        />

        <x-mary-input
            label="{{ __('setup.wizard.school_website') }}"
            type="url"
            placeholder="{{ __('setup.wizard.school_website_placeholder') }}"
            wire:model.live="schoolWebsite"
        />

        <x-mary-input
            label="{{ __('setup.wizard.school_address') }}"
            placeholder="{{ __('setup.wizard.school_address') }}"
            wire:model.live="schoolAddress"
        />

        <x-mary-input
            label="{{ __('setup.wizard.principal_name') }}"
            placeholder="{{ __('setup.wizard.principal_name_placeholder') }}"
            wire:model.live="principalName"
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
            icon="o-arrow-right"
            class="btn-primary btn-sm"
            wire:click="nextStep"
            spinner="nextStep"
        />
    </div>
</div>
