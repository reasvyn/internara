<div class="p-6 sm:p-8">
    <div class="mb-8">
        <h2 class="mb-1 text-xl font-bold">{{ __('setup.wizard.admin_account') }}</h2>
        <p class="text-base-content/50 text-sm">{{ __('setup.wizard.admin_subtitle') }}</p>
    </div>

    <div class="border-base-content/10 bg-base-200/30 mb-3 flex flex-wrap items-center gap-x-6 gap-y-2 rounded-lg border px-5 py-4">
        <div class="flex items-center gap-2 text-sm">
            <span class="text-base-content/50">{{ __('setup.wizard.full_name') }}:</span>
            <span class="font-semibold">{{ $superAdminForm->name }}</span>
        </div>
        <div class="flex items-center gap-2 text-sm">
            <span class="text-base-content/50">{{ __('setup.wizard.username') }}:</span>
            <code class="text-primary bg-primary/5 rounded px-2 py-0.5 font-mono font-semibold">{{ $superAdminForm->username }}</code>
        </div>
    </div>
    <p class="text-base-content/50 mb-8 text-xs leading-relaxed">{{ __('setup.wizard.username_notice') }}</p>

    <div class="space-y-5">
        <x-ts-input
            label="{{ __('setup.wizard.email_address') }}"
            type="email"
            wire:model.live.debounce.500ms="superAdminForm.email"
            icon="envelope"
            autofocus
        />

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <x-ts-password
                label="{{ __('setup.wizard.password') }}"
                wire:model.live.debounce.500ms="superAdminForm.password"
                icon="key"
                right
            />

            <x-ts-password
                label="{{ __('setup.wizard.confirm_password') }}"
                wire:model.live.debounce.500ms="superAdminForm.password_confirmation"
                icon="key"
                right
            />
        </div>

        <x-ts-alert color="info" :text="__('setup.wizard.password_hint')" icon="information-circle" class="mt-3 text-sm" />
    </div>

    <div class="border-base-content/10 mt-8 flex items-center justify-between border-t pt-6">
        <x-ts-button text="{{ __('setup.wizard.back') }}" wire:click="prevStep" color="white" sm />
        <x-ts-button
            text="{{ __('setup.wizard.next_step') }}"
            icon-right="o-arrow-right"
            color="primary"
            sm
            wire:click="nextStep"
            loading="nextStep"
        />
    </div>
</div>
