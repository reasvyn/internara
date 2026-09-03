<div class="p-6 sm:p-8">
    <div class="mb-8">
        <h2 class="mb-1 text-xl font-bold">{{ __('setup.wizard.finalize') }}</h2>
        <p class="text-base-content/50 text-sm">{{ __('setup.wizard.finalize_subtitle') }}</p>
    </div>

    <div class="mb-8 space-y-4">
        <label class="border-base-content/10 hover:bg-base-200/30 flex cursor-pointer items-start gap-4 rounded-lg border p-5 transition-colors">
            <input
                type="checkbox"
                wire:model.live="dataVerified"
                class="checkbox checkbox-primary checkbox-sm mt-0.5 rounded"
            />
            <div>
                <span class="mb-0.5 block text-sm font-semibold">{{ __('setup.wizard.data_verified') }}</span>
                <span class="text-base-content/50 text-xs">{{ __('setup.wizard.data_verified_long') }}</span>
            </div>
        </label>

        <label class="border-base-content/10 hover:bg-base-200/30 flex cursor-pointer items-start gap-4 rounded-lg border p-5 transition-colors">
            <input
                type="checkbox"
                wire:model.live="securityAware"
                class="checkbox checkbox-primary checkbox-sm mt-0.5 rounded"
            />
            <div>
                <span class="mb-0.5 block text-sm font-semibold">{{ __('setup.wizard.security_aware') }}</span>
                <span class="text-base-content/50 text-xs">{{ __('setup.wizard.security_aware_long') }}</span>
            </div>
        </label>
    </div>

    <div class="bg-base-200/30 mb-6 rounded-lg px-5 py-4">
        <h4 class="text-base-content/50 mb-3 text-xs font-semibold tracking-wider uppercase">
            {{ __('setup.wizard.summary') }}
        </h4>
        <dl class="grid grid-cols-1 gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
            <div class="flex justify-between">
                <dt class="text-base-content/50">{{ __('setup.wizard.school_name') }}</dt>
                <dd class="text-right font-medium">{{ $schoolForm->name ?: '—' }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-base-content/50">{{ __('setup.wizard.department_name') }}</dt>
                <dd class="text-right font-medium">{{ $departmentForm->name ?: '—' }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-base-content/50">{{ __('setup.wizard.full_name') }}</dt>
                <dd class="text-right font-medium">{{ $superAdminForm->name }}</dd>
            </div>
        </dl>
    </div>

    <div class="border-base-content/10 flex items-center justify-between border-t pt-6">
        <x-ts-button text="{{ __('setup.wizard.back') }}" wire:click="prevStep" color="slate" outline sm />
        <x-ts-button
            text="{{ __('setup.wizard.finish_setup') }}"
            icon="check"
            color="primary"
            wire:click="finish"
            loading="finish"
            x-bind:disabled="! ($wire.dataVerified && $wire.securityAware)"
        />
    </div>
</div>
