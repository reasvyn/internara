<div
    class="p-8 text-center sm:p-12"
    x-data="{ seconds: 19 }"
    x-init="
        let timer = setInterval(() => { if (seconds > 0) seconds--; }, 1000);
        setTimeout(() => { window.location.href = @js(route('login')); }, 20000);
    "
>
    <div
        class="border-success/20 bg-success/10 text-success ring-success/10 mx-auto mb-6 flex size-18 items-center justify-center rounded-2xl border shadow-xs ring-4"
        x-init="$el.querySelector('svg').style.animation = 'scaleIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) both'"
    >
        <x-ts-icon name="check" class="size-9" />
    </div>

    @push('styles')
        <style>
            @keyframes scaleIn {
                0% {
                    opacity: 0;
                    transform: scale(0);
                }
                100% {
                    opacity: 1;
                    transform: scale(1);
                }
            }
        </style>
    @endpush

    <h2 class="text-base-content mb-2 text-2xl font-black tracking-tight sm:text-3xl">
        {{ __('setup.wizard.setup_complete') }}
    </h2>
    <p class="text-base-content/60 mx-auto mb-8 max-w-md text-sm leading-relaxed">
        {{ __('setup.wizard.ready_desc') }}
    </p>

    <div class="border-base-content/10 bg-base-200/50 mx-auto mb-6 max-w-md rounded-2xl border p-6 text-left shadow-2xs">
        <h4 class="text-base-content/50 mb-4 text-center text-xs font-bold tracking-wider uppercase">
            {{ __('setup.wizard.access_summary') }}
        </h4>
        <dl class="space-y-3 text-sm">
            <div class="border-base-content/10 bg-base-100 flex items-center justify-between rounded-xl border px-4 py-3 shadow-2xs">
                <dt class="text-base-content/50 text-xs font-medium">{{ __('setup.wizard.username') }}</dt>
                <dd class="text-primary font-mono text-sm font-bold">{{ $superAdminForm->username }}</dd>
            </div>
            <div class="border-base-content/10 bg-base-100 flex items-center justify-between rounded-xl border px-4 py-3 shadow-2xs">
                <dt class="text-base-content/50 text-xs font-medium">{{ __('setup.wizard.email') }}</dt>
                <dd class="text-base-content text-sm font-semibold">{{ $superAdminForm->email }}</dd>
            </div>
        </dl>

        <div class="border-warning/20 bg-warning/5 mt-4 rounded-xl border px-4 py-3">
            <p class="text-warning/80 text-xs leading-relaxed">{{ __('setup.wizard.login_notice') }}</p>
        </div>
    </div>

    @if ($recoveryKey)
        <div class="border-warning/20 bg-warning/5 mx-auto mb-6 max-w-md rounded-2xl border p-6 text-left shadow-2xs">
            <h4 class="text-warning/70 mb-2 text-xs font-bold tracking-wider uppercase">
                {{ __('setup.wizard.recovery_key_title') }}
            </h4>
            <p class="text-warning/80 mb-3 text-xs leading-relaxed">{{ __('setup.wizard.recovery_key_desc') }}</p>
            <div
                class="border-warning/20 bg-base-100 flex items-center gap-2 rounded-xl border px-3.5 py-2.5 shadow-2xs"
                x-data="{ copied: false }"
            >
                <code class="text-warning flex-1 font-mono text-sm font-bold break-all select-all">{{ $recoveryKey }}</code>
                <button
                    type="button"
                    class="text-warning/70 hover:text-warning hover:bg-warning/10 flex size-8 shrink-0 items-center justify-center rounded-lg transition-colors"
                    x-on:click="
                        navigator.clipboard.writeText(@js($recoveryKey));
                        copied = true;
                        setTimeout(() => copied = false, 2000);
                    "
                    x-bind:title="copied ? @js(__('setup.wizard.copied')) : @js(__('setup.wizard.copy'))"
                    aria-label="{{ __('setup.wizard.copy') }}"
                >
                    <x-ts-icon name="clipboard-document" x-show="! copied" class="size-4" />
                    <x-ts-icon name="check" x-show="copied" class="text-success size-4" />
                </button>
            </div>
        </div>
    @endif

    <div class="mb-6 flex items-center justify-center gap-2">
        <span class="border-base-content/10 bg-base-200/60 text-base-content/70 inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1 font-mono text-xs shadow-2xs">
            <span class="bg-primary size-1.5 animate-pulse rounded-full"></span>
            {{ __('setup.wizard.auto_redirect_in') }}
            <span x-text="seconds" class="text-primary font-bold"></span>
            {{ __('setup.wizard.seconds') }}
        </span>
    </div>

    <x-ts-button
        text="{{ __('setup.wizard.go_to_login') }}"
        icon-right="arrow-right"
        color="primary"
        wire:click="finishSession"
    />
</div>
