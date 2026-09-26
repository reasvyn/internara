<div class="p-6 sm:p-10 lg:p-12">
    <div class="mb-8">
        <h2 class="text-base-content mb-1 text-2xl font-bold tracking-tight">{{ __('setup.wizard.admin_account') }}</h2>
        <p class="text-base-content/60 text-sm">{{ __('setup.wizard.admin_subtitle') }}</p>
    </div>

    {{-- Pre-configured Administrator Profile Card --}}
    <div class="border-base-content/10 bg-base-200/40 mb-3 flex flex-wrap items-center justify-between gap-4 rounded-3xl border p-5 shadow-2xs sm:p-6">
        <div class="flex items-center gap-4">
            <div class="border-primary/20 bg-primary/10 text-primary flex size-12 items-center justify-center rounded-2xl border shadow-2xs">
                <x-ts-icon name="shield-check" class="size-6 stroke-[2.2]" />
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <p class="text-base-content text-base font-bold">{{ $superAdminForm->name }}</p>
                    <span class="border-primary/20 bg-primary/10 text-primary rounded-full border px-2.5 py-0.5 text-[10px] font-bold tracking-wide uppercase">
                        Super Admin
                    </span>
                </div>
                <p class="text-base-content/50 text-xs font-medium">{{ __('setup.wizard.full_name') }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <span class="text-base-content/60 text-xs font-medium">{{ __('setup.wizard.username') }}:</span>
            <code class="border-primary/20 bg-primary/10 text-primary rounded-xl border px-3.5 py-1.5 font-mono text-xs font-bold tracking-wide">
                {{ $superAdminForm->username }}
            </code>
        </div>
    </div>

    <div class="border-base-content/10 bg-base-200/20 mb-8 flex items-start gap-2.5 rounded-xl border p-3.5 text-xs">
        <x-ts-icon name="information-circle" class="text-base-content/50 mt-0.5 size-4 shrink-0" />
        <p class="text-base-content/60 leading-relaxed">{{ __('setup.wizard.username_notice') }}</p>
    </div>

    <div class="space-y-6">
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

        {{-- Password Requirements Hint Card --}}
        <div class="border-info/20 bg-info/5 flex items-start gap-3.5 rounded-2xl border p-4 text-xs">
            <div class="border-info/20 bg-info/10 text-info mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-lg border">
                <x-ts-icon name="key" class="size-4" />
            </div>
            <div class="flex-1">
                <p class="text-base-content/80 mb-0.5 font-semibold">{{ __('setup.wizard.password_requirements') }}</p>
                <p class="text-base-content/60 leading-relaxed">{{ __('setup.wizard.password_hint') }}</p>
            </div>
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
