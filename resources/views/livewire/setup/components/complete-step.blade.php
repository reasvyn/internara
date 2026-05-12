<div class="p-8 sm:p-12 text-center">
    <div class="inline-flex items-center justify-center size-16 rounded-full bg-primary/10 text-primary mb-6">
        <x-mary-icon name="o-check" class="size-8" />
    </div>

    <h2 class="text-2xl font-bold mb-3">{{ __('setup.wizard.setup_complete') }}</h2>
    <p class="text-sm text-base-content/60 max-w-md mx-auto mb-10">
        {{ __('setup.wizard.ready_desc') }}
    </p>

    <div class="max-w-sm mx-auto bg-base-200/40 border border-base-content/10 rounded-xl p-6 mb-10 text-left">
        <h4 class="text-xs font-semibold uppercase tracking-wider text-base-content/40 mb-4 text-center">{{ __('setup.wizard.access_summary') }}</h4>
        <dl class="space-y-3 text-sm">
            <div class="flex items-center justify-between">
                <dt class="text-base-content/50">{{ __('setup.wizard.username') }}</dt>
                <dd class="font-mono font-semibold text-primary">{{ $adminUsername }}</dd>
            </div>
            <div class="border-t border-base-content/10"></div>
            <div class="flex items-center justify-between">
                <dt class="text-base-content/50">{{ __('setup.wizard.email') }}</dt>
                <dd class="font-medium">{{ $adminEmail }}</dd>
            </div>
        </dl>

        <div class="mt-5 bg-warning/5 border border-warning/20 rounded-lg px-4 py-3">
            <p class="text-xs text-warning/70 leading-relaxed">
                {{ __('setup.wizard.login_notice') }}
            </p>
        </div>
    </div>

    <x-mary-button
        label="{{ __('setup.wizard.go_to_login') }}"
        icon="o-arrow-right"
        class="btn-primary"
        wire:click="finishSession"
    />
</div>
