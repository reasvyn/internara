<div class="p-6 sm:p-10">
    <div class="mb-8">
        <h2 class="text-base-content mb-1 text-2xl font-bold tracking-tight">{{ __('setup.wizard.admin_account') }}</h2>
        <p class="text-base-content/60 text-sm">{{ __('setup.wizard.admin_subtitle') }}</p>
    </div>

    <div class="border-base-content/10 bg-base-200/50 mb-3 flex flex-wrap items-center justify-between gap-4 rounded-2xl border p-4 shadow-2xs sm:p-5">
        <div class="flex items-center gap-3.5">
            <div class="border-primary/20 bg-primary/10 text-primary flex size-11 items-center justify-center rounded-xl border">
                <x-ts-icon name="user" class="size-5" />
            </div>
            <div>
                <p class="text-base-content/50 text-xs font-medium">{{ __('setup.wizard.full_name') }}</p>
                <p class="text-base-content text-sm font-bold">{{ $superAdminForm->name }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-base-content/50 text-xs">{{ __('setup.wizard.username') }}:</span>
            <code class="border-primary/20 bg-primary/10 text-primary rounded-lg border px-3 py-1 font-mono text-xs font-bold">{{ $superAdminForm->username }}</code>
        </div>
    </div>
    <p class="text-base-content/50 mb-8 text-xs leading-relaxed">{{ __('setup.wizard.username_notice') }}</p>

    <div class="space-y-5">
        <x-ts-input
            :label="__('setup.wizard.email_address').' *'"
            type="email"
            wire:model.live.debounce.500ms="superAdminForm.email"
            icon="envelope"
            :placeholder="__('setup.wizard.email_placeholder')"
            autofocus
        />

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <x-ts-password
                :label="__('setup.wizard.password').' *'"
                wire:model.live.debounce.500ms="superAdminForm.password"
                icon="lock-closed"
                right
            />

            <x-ts-password
                :label="__('setup.wizard.confirm_password').' *'"
                wire:model.live.debounce.500ms="superAdminForm.password_confirmation"
                icon="lock-closed"
                right
            />
        </div>

        <x-ts-alert
            color="info"
            :text="__('setup.wizard.password_hint')"
            icon="information-circle"
            class="border-info/20 rounded-xl border text-xs sm:text-sm"
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
