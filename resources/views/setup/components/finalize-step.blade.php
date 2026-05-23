<div class="p-6 sm:p-8">
    <div class="mb-8">
        <h2 class="text-xl font-bold mb-1">{{ __('setup.wizard.finalize') }}</h2>
        <p class="text-sm text-base-content/50">{{ __('setup.wizard.finalize_subtitle') }}</p>
    </div>

    <div class="space-y-4 mb-8">
        <label class="flex items-start gap-4 p-5 rounded-lg border border-base-content/10 cursor-pointer transition-colors hover:bg-base-200/30">
            <input type="checkbox" wire:model.live.debounce.500ms="dataVerified" class="checkbox checkbox-primary checkbox-sm mt-0.5 rounded" />
            <div>
                <span class="font-semibold text-sm block mb-0.5">{{ __('setup.wizard.data_verified') }}</span>
                <span class="text-xs text-base-content/50">{{ __('setup.wizard.data_verified_long') }}</span>
            </div>
        </label>

        <label class="flex items-start gap-4 p-5 rounded-lg border border-base-content/10 cursor-pointer transition-colors hover:bg-base-200/30">
            <input type="checkbox" wire:model.live.debounce.500ms="securityAware" class="checkbox checkbox-primary checkbox-sm mt-0.5 rounded" />
            <div>
                <span class="font-semibold text-sm block mb-0.5">{{ __('setup.wizard.security_aware') }}</span>
                <span class="text-xs text-base-content/50">{{ __('setup.wizard.security_aware_long') }}</span>
            </div>
        </label>
    </div>

    <div class="bg-base-200/30 rounded-lg px-5 py-4 mb-6">
        <h4 class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-3">{{ __('setup.wizard.summary') }}</h4>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
            <div class="flex justify-between">
                <dt class="text-base-content/50">{{ __('setup.wizard.school_name') }}</dt>
                <dd class="font-medium text-right">{{ $schoolForm->name ?: '—' }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-base-content/50">{{ __('setup.wizard.department_name') }}</dt>
                <dd class="font-medium text-right">{{ $departmentForm->name ?: '—' }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-base-content/50">{{ __('setup.wizard.full_name') }}</dt>
                <dd class="font-medium text-right">{{ $adminForm->name }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-base-content/50">{{ __('setup.wizard.program_name') }}</dt>
                <dd class="font-medium text-right">{{ $internshipForm->name ?: '—' }}</dd>
            </div>
        </dl>
    </div>

    <div class="flex items-center justify-between pt-6 border-t border-base-content/10">
        <x-mary-button
            label="{{ __('setup.wizard.back') }}"
            wire:click="prevStep"
            class="btn-ghost btn-sm"
        />
        <x-mary-button
            label="{{ __('setup.wizard.finish_setup') }}"
            icon="o-check"
            class="btn-primary"
            wire:click="finish"
            spinner="finish"
            x-bind:disabled="!($wire.dataVerified && $wire.securityAware)"
        />
    </div>
</div>
