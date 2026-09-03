<div
    class="p-8 text-center sm:p-12"
    x-data="{ seconds: 19 }"
    x-init="
        let timer = setInterval(() => { if (seconds > 0) seconds--; }, 1000);
        setTimeout(() => { window.location.href = @js(route('login')); }, 20000);
    "
>
    <div
        class="bg-primary/10 text-primary mb-6 inline-flex size-16 items-center justify-center rounded-full"
        x-init="$el.querySelector('svg').style.animation = 'scaleIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) both'"
    >
        <x-ts-icon name="check" class="size-8" />
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

    <h2 class="mb-3 text-2xl font-bold">{{ __('setup.wizard.setup_complete') }}</h2>
    <p class="text-base-content/60 mx-auto mb-10 max-w-md text-sm">{{ __('setup.wizard.ready_desc') }}</p>

    <div class="bg-base-200/40 border-base-content/10 mx-auto mb-6 max-w-sm rounded-xl border p-6 text-left">
        <h4 class="text-base-content/40 mb-4 text-center text-xs font-semibold tracking-wider uppercase">
            {{ __('setup.wizard.access_summary') }}
        </h4>
        <dl class="space-y-3 text-sm">
            <div class="flex items-center justify-between">
                <dt class="text-base-content/50">{{ __('setup.wizard.username') }}</dt>
                <dd class="text-primary font-mono font-semibold">{{ $superAdminForm->username }}</dd>
            </div>
            <div class="border-base-content/10 border-t"></div>
            <div class="flex items-center justify-between">
                <dt class="text-base-content/50">{{ __('setup.wizard.email') }}</dt>
                <dd class="font-medium">{{ $superAdminForm->email }}</dd>
            </div>
        </dl>

        <div class="bg-warning/5 border-warning/20 mt-5 rounded-lg border px-4 py-3">
            <p class="text-warning/70 text-xs leading-relaxed">{{ __('setup.wizard.login_notice') }}</p>
        </div>
    </div>

    @if ($recoveryKey)
        <div class="bg-warning/5 border-warning/20 mx-auto mb-6 max-w-sm rounded-xl border p-6 text-left">
            <h4 class="text-warning/60 mb-2 text-xs font-semibold tracking-wider uppercase">
                {{ __('setup.wizard.recovery_key_title') }}
            </h4>
            <p class="text-warning/70 mb-3 text-xs leading-relaxed">{{ __('setup.wizard.recovery_key_desc') }}</p>
            <div
                class="bg-base-100 border-warning/10 flex items-center gap-2 rounded-lg border px-3 py-2.5"
                x-data="{ copied: false }"
            >
                <code class="text-warning flex-1 font-mono text-sm font-bold break-all select-all">{{ $recoveryKey }}</code>
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
                    <x-ts-icon name="clipboard-document" x-show="! copied" class="size-4" />
                    <x-ts-icon name="check" x-show="copied" class="text-success size-4" />
                </button>
            </div>
        </div>
    @endif

    <p class="text-base-content/40 mb-4 text-sm">
        {{ __('setup.wizard.auto_redirect_in') }}
        <span x-text="seconds" class="text-base-content/60 font-mono font-semibold"></span>
        {{ __('setup.wizard.seconds') }}
    </p>

    <x-ts-button
        text="{{ __('setup.wizard.go_to_login') }}"
        icon-right="arrow-right"
        color="primary"
        wire:click="finishSession"
    />
</div>
