<div class="flex w-full items-center justify-center py-4">
    <div class="w-full max-w-md space-y-4">

        {{-- Main Card --}}
        <x-ui::card wire:key="verification-notice-card" class="w-full">
            <div class="flex flex-col items-center gap-6 py-2 text-center">

                {{-- Icon --}}
                <div class="flex size-20 items-center justify-center rounded-full bg-info/10">
                    <x-ui::icon name="tabler.mail-check" class="size-10 text-info" />
                </div>

                {{-- Heading --}}
                <div>
                    <h2 class="text-xl font-bold text-base-content">{{ __('auth::ui.verification.title') }}</h2>
                    <p class="mt-1 text-sm text-base-content/60">{{ __('auth::ui.verification.subtitle') }}</p>
                </div>

                {{-- Info --}}
                <p class="text-sm text-base-content/70 leading-relaxed">
                    {{ __('auth::ui.verification.notice') }}
                </p>

                {{-- Email pill --}}
                @if(auth()->user()?->email)
                    <div class="flex w-full items-center gap-3 rounded-xl bg-base-200/60 px-4 py-3 text-left">
                        <x-ui::icon name="tabler.mail" class="size-5 shrink-0 text-info" />
                        <div class="min-w-0">
                            <p class="text-xs text-base-content/50">{{ __('auth::ui.verification.sent_to') }}</p>
                            <p class="truncate text-sm font-semibold font-mono">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                @endif

                {{-- Flash status --}}
                @if(session('status'))
                    <x-ui::alert type="success" class="w-full text-left" :description="session('status')" />
                @endif

                {{-- Resend button --}}
                <div class="w-full space-y-2">
                    <x-ui::button
                        variant="primary"
                        class="w-full"
                        icon="tabler.mail-forward"
                        :label="__('auth::ui.verification.resend_button')"
                        wire:click="resend"
                        spinner="resend"
                    />
                </div>

                {{-- Tips --}}
                <p class="text-xs text-base-content/40 leading-relaxed">
                    <span class="font-semibold">{{ __('auth::ui.verification.tips_title') }}</span>
                    {{ __('auth::ui.verification.tips', ['app' => setting('site_title', 'Internara')]) }}
                </p>
            </div>
        </x-ui::card>

        {{-- Logout link --}}
        <div class="text-center">
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-sm text-base-content/50 hover:text-base-content transition-colors">
                    ← {{ __('auth::ui.verification.logout') }}
                </button>
            </form>
        </div>

    </div>
</div>