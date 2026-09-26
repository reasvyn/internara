<div class="p-6 sm:p-10 lg:p-12">
    <div class="mb-8">
        <h2 class="text-base-content mb-1 text-2xl font-bold tracking-tight">{{ __('setup.wizard.school_info') }}</h2>
        <p class="text-base-content/60 text-sm">{{ __('setup.wizard.school_subtitle') }}</p>
    </div>

    {{-- Identity Context Banner --}}
    <div class="border-base-content/10 bg-base-200/40 mb-8 flex items-start gap-3.5 rounded-2xl border p-4 text-xs leading-relaxed shadow-2xs sm:p-4.5">
        <div class="border-primary/20 bg-primary/10 text-primary mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-xl border">
            <x-ts-icon name="academic-cap" class="size-4" />
        </div>
        <p class="text-base-content/70 pt-0.5">{{ __('setup.wizard.school_identity_notice') }}</p>
    </div>

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
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
                rows="3"
            />
        </div>
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
