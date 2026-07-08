<div class="p-8 sm:p-12 text-center"
    x-data="{ seconds: 19 }"
    x-init="
        let timer = setInterval(() => { if (seconds > 0) seconds--; }, 1000);
        setTimeout(() => { window.location.href = @js(route('login')); }, 20000);
    "
>
    <div class="inline-flex items-center justify-center size-16 rounded-full bg-primary/10 text-primary mb-6">
        <x-mary-icon name="o-check" class="size-8" />
    </div>

    <h2 class="text-2xl font-bold mb-3">{{ __('setup.wizard.setup_complete') }}</h2>
    <p class="text-sm text-base-content/60 max-w-md mx-auto mb-10">
        {{ __('setup.wizard.ready_desc') }}
    </p>

    <div class="max-w-sm mx-auto bg-base-200/40 border border-base-content/10 rounded-xl p-6 mb-6 text-left">
        <h4 class="text-xs font-semibold uppercase tracking-wider text-base-content/40 mb-4 text-center">{{ __('setup.wizard.access_summary') }}</h4>
        <dl class="space-y-3 text-sm">
            <div class="flex items-center justify-between">
                <dt class="text-base-content/50">{{ __('setup.wizard.username') }}</dt>
                <dd class="font-mono font-semibold text-primary">{{ $superAdminForm->username }}</dd>
            </div>
            <div class="border-t border-base-content/10"></div>
            <div class="flex items-center justify-between">
                <dt class="text-base-content/50">{{ __('setup.wizard.email') }}</dt>
                <dd class="font-medium">{{ $superAdminForm->email }}</dd>
            </div>
        </dl>

        <div class="mt-5 bg-warning/5 border border-warning/20 rounded-lg px-4 py-3">
            <p class="text-xs text-warning/70 leading-relaxed">
                {{ __('setup.wizard.login_notice') }}
            </p>
        </div>
    </div>

    @if($recoveryKey)
    <div class="max-w-sm mx-auto bg-warning/5 border border-warning/20 rounded-xl p-6 mb-6 text-left">
        <h4 class="text-xs font-semibold uppercase tracking-wider text-warning/60 mb-2">{{ __('setup.wizard.recovery_key_title') }}</h4>
        <p class="text-xs text-warning/70 leading-relaxed mb-3">
            {{ __('setup.wizard.recovery_key_desc') }}
        </p>
        <div
            class="bg-base-100 rounded-lg px-3 py-2.5 border border-warning/10 flex items-center gap-2"
            x-data="{ copied: false }"
        >
            <code class="text-sm font-mono font-bold text-warning break-all select-all flex-1">{{ $recoveryKey }}</code>
            <button
                type="button"
                class="btn btn-xs btn-ghost text-warning/60 hover:text-warning shrink-0"
                x-on:click="
                    navigator.clipboard.writeText(@js($recoveryKey));
                    copied = true;
                    setTimeout(() => copied = false, 2000);
                "
                x-bind:title="copied ? @js(__('setup.wizard.copied')) : @js(__('setup.wizard.copy'))"
            >
                <x-mary-icon name="o-clipboard-document" x-show="!copied" class="size-4" />
                <x-mary-icon name="o-check" x-show="copied" class="size-4 text-success" />
            </button>
        </div>
    </div>
    @endif

    <p class="text-sm text-base-content/40 mb-4">
        {{ __('setup.wizard.auto_redirect_in') }} <span x-text="seconds" class="font-mono font-semibold text-base-content/60"></span> {{ __('setup.wizard.seconds') }}
    </p>

    <x-mary-button
        label="{{ __('setup.wizard.go_to_login') }}"
        icon-right="o-arrow-right"
        class="btn-primary"
        wire:click="finishSession"
    />
</div>
