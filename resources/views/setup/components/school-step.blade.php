<div class="p-4.5 sm:p-6 lg:p-7">
    <div class="mb-4 sm:mb-5">
        <h2 class="text-base-content mb-1 text-lg font-bold tracking-tight sm:text-2xl">
            {{ __('setup.wizard.school_info') }}
        </h2>
        <p class="text-base-content/60 text-xs sm:text-sm">{{ __('setup.wizard.school_subtitle') }}</p>
    </div>

    {{-- Identity Context Banner --}}
    <div class="border-base-content/10 bg-base-200/40 mb-4 flex items-start gap-3 rounded-md border p-3 text-xs leading-relaxed shadow-2xs sm:mb-5 sm:p-3.5">
        <div class="border-primary/20 bg-primary/10 text-primary mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-md border sm:size-8">
            <x-ts-icon name="academic-cap" class="size-4" />
        </div>
        <p class="text-base-content/70 pt-0.5 text-[11px] sm:text-xs">
            {{ __('setup.wizard.school_identity_notice') }}
        </p>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:gap-3.5 md:grid-cols-2">
        <div class="md:col-span-2">
            <x-ts-input
                :label="__('setup.wizard.school_name').' *'"
                placeholder="{{ __('setup.wizard.school_name_placeholder') }}"
                wire:model.live.debounce.500ms="schoolForm.name"
                icon="academic-cap"
                autofocus
            />
        </div>

        <x-ts-input
            :label="__('setup.wizard.school_code').' *'"
            placeholder="{{ __('setup.wizard.school_code_placeholder') }}"
            wire:model.live.debounce.500ms="schoolForm.institutional_code"
            icon="identification"
        />

        <x-ts-input
            :label="__('setup.wizard.school_email').' *'"
            type="email"
            placeholder="{{ __('setup.wizard.school_email_placeholder') }}"
            wire:model.live.debounce.500ms="schoolForm.email"
            icon="envelope"
        />

        <x-ts-input
            :label="__('setup.wizard.school_phone')"
            type="tel"
            placeholder="{{ __('setup.wizard.school_phone_placeholder') }}"
            wire:model.live.debounce.500ms="schoolForm.phone"
            icon="phone"
        />

        <x-ts-input
            :label="__('setup.wizard.school_website')"
            type="url"
            placeholder="{{ __('setup.wizard.school_website_placeholder') }}"
            wire:model.live.debounce.500ms="schoolForm.website"
            icon="globe-alt"
        />

        <div class="md:col-span-2">
            <x-ts-input
                :label="__('setup.wizard.principal_name')"
                placeholder="{{ __('setup.wizard.principal_name_placeholder') }}"
                wire:model.live.debounce.500ms="schoolForm.principal_name"
                icon="user"
            />
        </div>

        <div class="md:col-span-2">
            <x-ts-textarea
                :label="__('setup.wizard.school_address')"
                placeholder="{{ __('setup.wizard.school_address_placeholder') }}"
                wire:model.live.debounce.500ms="schoolForm.address"
                rows="2"
            />
        </div>
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
