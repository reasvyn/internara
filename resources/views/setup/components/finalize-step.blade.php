<div class="p-6 sm:p-10">
    <div class="mb-8">
        <h2 class="text-base-content mb-1 text-2xl font-bold tracking-tight">{{ __('setup.wizard.finalize') }}</h2>
        <p class="text-base-content/60 text-sm">{{ __('setup.wizard.finalize_subtitle') }}</p>
    </div>

    <div class="mb-8 space-y-4">
        <label class="border-base-content/10 bg-base-200/40 hover:border-primary/40 hover:bg-base-200/70 flex cursor-pointer items-start gap-4 rounded-2xl border p-5 shadow-2xs transition-all">
            <x-ts-checkbox wire:model.live="dataVerified" color="primary" class="mt-0.5" />
            <div class="select-none">
                <span class="text-base-content mb-0.5 block text-sm font-semibold">{{ __('setup.wizard.data_verified') }}</span>
                <span class="text-base-content/60 text-xs leading-relaxed">{{ __('setup.wizard.data_verified_long') }}</span>
            </div>
        </label>

        <label class="border-base-content/10 bg-base-200/40 hover:border-primary/40 hover:bg-base-200/70 flex cursor-pointer items-start gap-4 rounded-2xl border p-5 shadow-2xs transition-all">
            <x-ts-checkbox wire:model.live="securityAware" color="primary" class="mt-0.5" />
            <div class="select-none">
                <span class="text-base-content mb-0.5 block text-sm font-semibold">{{ __('setup.wizard.security_aware') }}</span>
                <span class="text-base-content/60 text-xs leading-relaxed">{{ __('setup.wizard.security_aware_long') }}</span>
            </div>
        </label>
    </div>

    <div class="border-base-content/10 bg-base-200/50 mb-8 rounded-2xl border p-6 shadow-2xs">
        <div class="mb-4 flex items-center gap-2">
            <div class="border-primary/20 bg-primary/10 text-primary flex size-7 items-center justify-center rounded-lg border">
                <x-ts-icon name="document-check" class="size-4" />
            </div>
            <h4 class="text-base-content/80 text-xs font-bold tracking-wider uppercase">
                {{ __('setup.wizard.summary') }}
            </h4>
        </div>
        <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
            <div class="border-base-content/10 bg-base-100 flex items-center justify-between rounded-xl border px-4 py-3 shadow-2xs">
                <dt class="text-base-content/50 text-xs font-medium">{{ __('setup.wizard.school_name') }}</dt>
                <dd class="text-base-content font-bold">{{ $schoolForm->name ?: '—' }}</dd>
            </div>
            <div class="border-base-content/10 bg-base-100 flex items-center justify-between rounded-xl border px-4 py-3 shadow-2xs">
                <dt class="text-base-content/50 text-xs font-medium">{{ __('setup.wizard.department_name') }}</dt>
                <dd class="text-base-content font-bold">{{ $departmentForm->name ?: '—' }}</dd>
            </div>
            <div class="border-base-content/10 bg-base-100 flex items-center justify-between rounded-xl border px-4 py-3 shadow-2xs sm:col-span-2">
                <dt class="text-base-content/50 text-xs font-medium">{{ __('setup.wizard.full_name') }}</dt>
                <dd class="text-base-content font-bold">{{ $superAdminForm->name }}</dd>
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
