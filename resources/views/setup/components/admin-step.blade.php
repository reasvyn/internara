<div class="p-6 sm:p-8">
    <div class="mb-8">
        <h2 class="text-xl font-bold mb-1">{{ __('setup.wizard.admin_account') }}</h2>
        <p class="text-sm text-base-content/50">{{ __('setup.wizard.admin_subtitle') }}</p>
    </div>

    <div class="flex flex-wrap items-center gap-x-6 gap-y-2 rounded-lg border border-base-content/10 bg-base-200/30 px-5 py-4 mb-8">
        <div class="flex items-center gap-2 text-sm">
            <span class="text-base-content/50">{{ __('setup.wizard.full_name') }}:</span>
            <span class="font-semibold">{{ $adminData['name'] }}</span>
        </div>
        <div class="flex items-center gap-2 text-sm">
            <span class="text-base-content/50">{{ __('setup.wizard.username') }}:</span>
            <code class="font-mono font-semibold text-primary bg-primary/5 px-2 py-0.5 rounded">{{ $adminData['username'] }}</code>
        </div>
    </div>

    <div class="space-y-5">
        <x-mary-input
            label="{{ __('setup.wizard.email_address') }}"
            type="email"
            wire:model.live="adminData.email"
        />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <x-mary-input
                label="{{ __('setup.wizard.password') }}"
                type="password"
                wire:model.live="adminData.password"
            />

            <x-mary-input
                label="{{ __('setup.wizard.confirm_password') }}"
                type="password"
                wire:model.live="adminData.password_confirmation"
            />
        </div>
    </div>

    <div class="flex items-center justify-between pt-6 mt-8 border-t border-base-content/10">
        <x-mary-button
            label="{{ __('setup.wizard.back') }}"
            wire:click="prevStep"
            class="btn-ghost btn-sm"
        />
        <x-mary-button
            label="{{ __('setup.wizard.next_step') }}"
            icon="o-arrow-right"
            class="btn-primary btn-sm"
            wire:click="nextStep"
            spinner="nextStep"
        />
    </div>
</div>
