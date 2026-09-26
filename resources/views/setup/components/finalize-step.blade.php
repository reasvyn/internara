<div class="p-6 sm:p-10 lg:p-12">
    <div class="mb-8">
        <h2 class="text-base-content mb-1 text-2xl font-bold tracking-tight">{{ __('setup.wizard.finalize') }}</h2>
        <p class="text-base-content/60 text-sm">{{ __('setup.wizard.finalize_subtitle') }}</p>
    </div>

    {{-- Interactive Verification Cards --}}
    <div class="mb-8 space-y-3.5">
        <label @class([
            'flex items-start gap-4 p-5 rounded-2xl border transition-all cursor-pointer shadow-2xs select-none',
            'border-primary/40 bg-primary/5 ring-2 ring-primary/10' => $dataVerified,
            'border-base-content/10 bg-base-200/40 hover:border-base-content/20 hover:bg-base-200/60' => ! $dataVerified,
        ])>
            <x-ts-checkbox wire:model.live="dataVerified" color="primary" class="mt-0.5" />
            <div class="flex-1">
                <span class="text-base-content mb-0.5 block text-sm font-bold">{{ __('setup.wizard.data_verified') }}</span>
                <span class="text-base-content/60 block text-xs leading-relaxed">{{ __('setup.wizard.data_verified_long') }}</span>
            </div>
        </label>

        <label @class([
            'flex items-start gap-4 p-5 rounded-2xl border transition-all cursor-pointer shadow-2xs select-none',
            'border-primary/40 bg-primary/5 ring-2 ring-primary/10' => $securityAware,
            'border-base-content/10 bg-base-200/40 hover:border-base-content/20 hover:bg-base-200/60' => ! $securityAware,
        ])>
            <x-ts-checkbox wire:model.live="securityAware" color="primary" class="mt-0.5" />
            <div class="flex-1">
                <span class="text-base-content mb-0.5 block text-sm font-bold">{{ __('setup.wizard.security_aware') }}</span>
                <span class="text-base-content/60 block text-xs leading-relaxed">{{ __('setup.wizard.security_aware_long') }}</span>
            </div>
        </label>
    </div>

    {{-- Configuration Summary Card --}}
    <div class="border-base-content/10 bg-base-200/40 mb-8 rounded-3xl border p-6 shadow-2xs">
        <div class="border-base-content/10 mb-5 flex items-center justify-between border-b pb-4">
            <div class="flex items-center gap-2.5">
                <div class="border-primary/20 bg-primary/10 text-primary flex size-8 items-center justify-center rounded-xl border">
                    <x-ts-icon name="document-check" class="size-4" />
                </div>
                <h4 class="text-base-content text-sm font-bold">{{ __('setup.wizard.summary') }}</h4>
            </div>
            <span class="border-base-content/10 bg-base-100 text-base-content/60 rounded-full border px-2.5 py-0.5 font-mono text-[11px] font-semibold">
                {{ __('setup.wizard.readiness_audit') }}
            </span>
        </div>

        <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="border-base-content/10 bg-base-100 flex items-center justify-between rounded-xl border p-3.5 shadow-2xs">
                <div class="flex min-w-0 items-center gap-2.5">
                    <x-ts-icon name="academic-cap" class="text-base-content/40 size-4 shrink-0" />
                    <div>
                        <dt class="text-base-content/50 text-[11px] font-medium">
                            {{ __('setup.wizard.school_name') }}
                        </dt>
                        <dd class="text-base-content truncate text-xs font-bold">{{ $schoolForm->name ?: '—' }}</dd>
                    </div>
                </div>
            </div>

            <div class="border-base-content/10 bg-base-100 flex items-center justify-between rounded-xl border p-3.5 shadow-2xs">
                <div class="flex min-w-0 items-center gap-2.5">
                    <x-ts-icon name="building-library" class="text-base-content/40 size-4 shrink-0" />
                    <div>
                        <dt class="text-base-content/50 text-[11px] font-medium">
                            {{ __('setup.wizard.department_name') }}
                        </dt>
                        <dd class="text-base-content truncate text-xs font-bold">{{ $departmentForm->name ?: '—' }}</dd>
                    </div>
                </div>
            </div>

            <div class="border-base-content/10 bg-base-100 flex items-center justify-between rounded-xl border p-3.5 shadow-2xs sm:col-span-2">
                <div class="flex min-w-0 items-center gap-2.5">
                    <x-ts-icon name="shield-check" class="text-base-content/40 size-4 shrink-0" />
                    <div>
                        <dt class="text-base-content/50 text-[11px] font-medium">{{ __('setup.wizard.full_name') }}</dt>
                        <dd class="text-base-content text-xs font-bold">
                            {{ $superAdminForm->name }} ({{ $superAdminForm->username }})
                        </dd>
                    </div>
                </div>
                <span class="text-base-content/60 font-mono text-xs">{{ $superAdminForm->email }}</span>
            </div>
        </dl>
    </div>

    <div class="border-base-content/10 mt-10 flex items-center justify-between border-t pt-6">
        <x-ts-button :text="__('setup.wizard.back')" wire:click="prevStep" color="slate" outline sm icon="arrow-left" />
        <x-ts-button
            :text="__('setup.wizard.finish_setup')"
            icon="check"
            color="primary"
            sm
            wire:click="finish"
            loading="finish"
            x-bind:disabled="! ($wire.dataVerified && $wire.securityAware)"
        />
    </div>
</div>
