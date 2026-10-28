<div class="p-4.5 sm:p-6 lg:p-7">
    <div class="mb-4 sm:mb-5">
        <h2 class="text-base-content mb-1 text-xl font-bold tracking-tight">
            {{ __('setup.wizard.admin_account') }}
        </h2>
        <p class="text-base-content/60 text-xs sm:text-sm">{{ __('setup.wizard.admin_subtitle') }}</p>
    </div>

    {{-- Pre-configured Administrator Profile Card --}}
    <div class="border-base-content/10 bg-base-200/40 mb-2.5 flex flex-wrap items-center justify-between gap-3 rounded-lg border p-3.5 shadow-2xs sm:p-4.5">
        <div class="flex items-center gap-3">
            <div class="border-primary/20 bg-primary/10 text-primary flex size-10 items-center justify-center rounded-md border shadow-2xs sm:size-11">
                <x-ts-icon name="shield-check" class="size-5 stroke-[2.2] sm:size-6" />
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <p class="text-base-content text-sm font-bold sm:text-base">{{ $superAdminForm->name }}</p>
                    <span class="border-primary/20 bg-primary/10 text-primary rounded-sm border px-2 py-0.5 text-[9px] font-bold tracking-wide uppercase sm:text-[10px]">
                        Super Admin
                    </span>
                </div>
                <p class="text-base-content/50 text-[11px] font-medium sm:text-xs">
                    {{ __('setup.wizard.full_name') }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-base-content/60 text-xs font-medium">{{ __('setup.wizard.username') }}:</span>
            <code class="border-primary/20 bg-primary/10 text-primary rounded-md border px-2.5 py-1 font-mono text-xs font-bold tracking-wide sm:px-3">
                {{ $superAdminForm->username }}
            </code>
        </div>
    </div>

    <div class="border-base-content/10 bg-base-200/20 mb-4 flex items-start gap-2.5 rounded-md border p-2.5 text-xs sm:mb-5 sm:p-3">
        <x-ts-icon name="information-circle" class="text-base-content/50 mt-0.5 size-4 shrink-0" />
        <p class="text-base-content/60 text-[11px] leading-relaxed sm:text-xs">
            {{ __('setup.wizard.username_notice') }}
        </p>
    </div>

    <div class="space-y-4 sm:space-y-4.5">
        <x-ts-input
            :label="__('setup.wizard.email_address').' *'"
            type="email"
            wire:model="superAdminForm.email"
            icon="envelope"
            :placeholder="__('setup.wizard.email_placeholder')"
            autofocus
        />

        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 sm:gap-4">
            <x-ts-password
                :label="__('setup.wizard.password').' *'"
                wire:model="superAdminForm.password"
                icon="lock-closed"
                right
            />

            <x-ts-password
                :label="__('setup.wizard.confirm_password').' *'"
                wire:model="superAdminForm.password_confirmation"
                icon="lock-closed"
                right
            />
        </div>

        {{-- Password Requirements Hint Card --}}
        <div class="border-info/20 bg-info/5 flex items-start gap-3 rounded-md border p-3 text-xs sm:p-3.5">
            <div class="border-info/20 bg-info/10 text-info mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-md border sm:size-7">
                <x-ts-icon name="key" class="size-3.5 sm:size-4" />
            </div>
            <div class="flex-1">
                <p class="text-base-content/80 mb-0.5 text-xs font-semibold">
                    {{ __('setup.wizard.password_requirements') }}
                </p>
                <p class="text-base-content/60 text-[11px] leading-relaxed sm:text-xs">
                    {{ __('setup.wizard.password_hint') }}
                </p>
            </div>
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
