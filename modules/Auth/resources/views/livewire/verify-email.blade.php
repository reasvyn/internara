<div class="flex w-full items-center justify-center py-4">
    <div class="w-full max-w-md space-y-4">

        <x-ui::card wire:key="verify-email-card" class="w-full">
            <div class="flex flex-col items-center gap-6 py-2 text-center">

                {{-- Processing indicator --}}
                <div wire:loading class="flex size-20 items-center justify-center rounded-full bg-info/10">
                    <x-ui::icon name="tabler.loader-2" class="size-10 animate-spin text-info" />
                </div>

                <div wire:loading>
                    <h2 class="text-xl font-bold text-base-content">{{ __('auth::ui.verification.processing') }}</h2>
                </div>

                {{-- Flash messages (shown after verify() redirects back) --}}
                @if(session('status') || session('error'))
                    <div class="w-full space-y-3">
                        @if(session('status'))
                            <div class="flex size-20 items-center justify-center rounded-full bg-success/10 mx-auto">
                                <x-ui::icon name="tabler.circle-check" class="size-10 text-success" />
                            </div>
                            <h2 class="text-xl font-bold text-base-content">{{ __('auth::ui.verification.success') }}</h2>
                        @endif
                        @if(session('error'))
                            <div class="flex size-20 items-center justify-center rounded-full bg-error/10 mx-auto">
                                <x-ui::icon name="tabler.circle-x" class="size-10 text-error" />
                            </div>
                        @endif
                        @if(session('status'))
                            <x-ui::alert type="success" class="w-full text-left" :description="session('status')" />
                        @endif
                        @if(session('error'))
                            <x-ui::alert type="error" class="w-full text-left" :description="session('error')" />
                        @endif
                    </div>
                @endif

                {{-- Resend option --}}
                <div class="w-full space-y-3">
                    <p class="text-xs text-base-content/50">{{ __('auth::ui.verification.resend_prompt') }}</p>
                    <x-ui::button
                        variant="secondary"
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
