<div class="p-4.5 sm:p-6 lg:p-7">
    <div class="mb-4 sm:mb-5">
        <h2 class="text-base-content mb-1 text-lg font-bold tracking-tight sm:text-2xl">
            {{ __('setup.wizard.finalize') }}
        </h2>
        <p class="text-base-content/60 text-xs sm:text-sm">{{ __('setup.wizard.finalize_subtitle') }}</p>
    </div>

    {{-- Interactive Verification Cards --}}
    <div class="mb-4 space-y-2 sm:mb-5 sm:space-y-2.5">
        <label @class([
            'flex items-start gap-3 sm:gap-4 p-3.5 sm:p-4 rounded-xl sm:rounded-2xl border transition-all cursor-pointer shadow-2xs select-none',
            'border-primary/40 bg-primary/5 ring-2 ring-primary/10' => $dataVerified,
            'border-base-content/10 bg-base-200/40 hover:border-base-content/20 hover:bg-base-200/60' => ! $dataVerified,
        ])>
            <x-ts-checkbox wire:model.live="dataVerified" color="primary" class="mt-0.5" />
            <div class="flex-1">
                <span class="text-base-content mb-0.5 block text-xs font-bold sm:text-sm">{{ __('setup.wizard.data_verified') }}</span>
                <span class="text-base-content/60 block text-[11px] leading-relaxed sm:text-xs">{{ __('setup.wizard.data_verified_long') }}</span>
            </div>
        </label>

        <label @class([
            'flex items-start gap-3 sm:gap-4 p-3.5 sm:p-4 rounded-xl sm:rounded-2xl border transition-all cursor-pointer shadow-2xs select-none',
            'border-primary/40 bg-primary/5 ring-2 ring-primary/10' => $securityAware,
            'border-base-content/10 bg-base-200/40 hover:border-base-content/20 hover:bg-base-200/60' => ! $securityAware,
        ])>
            <x-ts-checkbox wire:model.live="securityAware" color="primary" class="mt-0.5" />
            <div class="flex-1">
                <span class="text-base-content mb-0.5 block text-xs font-bold sm:text-sm">{{ __('setup.wizard.security_aware') }}</span>
                <span class="text-base-content/60 block text-[11px] leading-relaxed sm:text-xs">{{ __('setup.wizard.security_aware_long') }}</span>
            </div>
        </label>
    </div>

    {{-- Configuration Summary Card --}}
    <div class="border-base-content/10 bg-base-200/40 mb-4 rounded-2xl border p-4 shadow-2xs sm:mb-5 sm:rounded-3xl sm:p-5">
        <div class="border-base-content/10 mb-3.5 flex items-center justify-between border-b pb-2.5 sm:pb-3">
            <div class="flex items-center gap-2 sm:gap-2.5">
                <div class="border-primary/20 bg-primary/10 text-primary flex size-7 items-center justify-center rounded-lg border sm:size-8 sm:rounded-xl">
                    <x-ts-icon name="document-check" class="size-3.5 sm:size-4" />
                </div>
                <h4 class="text-base-content text-xs font-bold sm:text-sm">{{ __('setup.wizard.summary') }}</h4>
            </div>
            <span class="border-base-content/10 bg-base-100 text-base-content/60 rounded-full border px-2 py-0.5 font-mono text-[10px] font-semibold sm:px-2.5 sm:text-[11px]">
                {{ __('setup.wizard.readiness_audit') }}
            </span>
        </div>

        <dl class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 sm:gap-3">
            <div class="border-base-content/10 bg-base-100 flex items-center justify-between rounded-xl border p-2.5 shadow-2xs sm:p-3">
                <div class="flex min-w-0 items-center gap-2 sm:gap-2.5">
                    <x-ts-icon name="academic-cap" class="text-base-content/40 size-4 shrink-0" />
                    <div>
                        <dt class="text-base-content/50 text-[10px] font-medium sm:text-[11px]">
                            {{ __('setup.wizard.school_name') }}
                        </dt>
                        <dd class="text-base-content truncate text-xs font-bold">{{ $schoolForm->name ?: '—' }}</dd>
                    </div>
                </div>
            </div>

            <div class="border-base-content/10 bg-base-100 flex items-center justify-between rounded-xl border p-2.5 shadow-2xs sm:p-3">
                <div class="flex min-w-0 items-center gap-2 sm:gap-2.5">
                    <x-ts-icon name="building-library" class="text-base-content/40 size-4 shrink-0" />
                    <div>
                        <dt class="text-base-content/50 text-[10px] font-medium sm:text-[11px]">
                            {{ __('setup.wizard.department_name') }}
                        </dt>
                        <dd class="text-base-content truncate text-xs font-bold">{{ $departmentForm->name ?: '—' }}</dd>
                    </div>
                </div>
            </div>

            <div class="border-base-content/10 bg-base-100 flex items-center justify-between rounded-xl border p-2.5 shadow-2xs sm:col-span-2 sm:p-3">
                <div class="flex min-w-0 items-center gap-2 sm:gap-2.5">
                    <x-ts-icon name="shield-check" class="text-base-content/40 size-4 shrink-0" />
                    <div>
                        <dt class="text-base-content/50 text-[10px] font-medium sm:text-[11px]">
                            {{ __('setup.wizard.full_name') }}
                        </dt>
                        <dd class="text-base-content text-xs font-bold">
                            {{ $superAdminForm->name }} ({{ $superAdminForm->username }})
                        </dd>
                    </div>
                </div>
                <span class="text-base-content/60 font-mono text-[11px] sm:text-xs">{{ $superAdminForm->email }}</span>
            </div>
        </dl>
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
            :text="__('setup.wizard.finish_setup')"
            icon="check"
            position="right"
            color="primary"
            wire:click="finish"
            loading="finish"
            x-bind:disabled="! ($wire.dataVerified && $wire.securityAware)"
        />
    </div>
</div>
