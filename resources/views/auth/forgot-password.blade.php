<div>
    <div class="bg-base-100 border border-base-content/10 rounded-xl p-6">
        <div class="text-center mb-6">
            <h2 class="text-lg font-bold">{{ __('passwords.reset_password_title') }}</h2>
            <p class="text-sm text-base-content/50 mt-1">{{ __('auth.forgot_password.subtitle') }}</p>
        </div>

        @if ($linkSent)
            <div class="bg-success/5 border border-success/20 rounded-xl p-8 text-center">
                <div class="size-12 rounded-full bg-success/10 text-success flex items-center justify-center mx-auto mb-4">
                    <x-mary-icon name="o-check-circle" class="size-6" />
                </div>
                <h3 class="font-semibold text-success mb-2">{{ __('passwords.sent') }}</h3>
                <p class="text-xs text-success/70 mb-6">{{ __('passwords.sent_detail') }}</p>
                <x-mary-button
                    link="{{ route('login') }}"
                    label="{{ __('auth.login.back_to_login') }}"
                    icon="o-arrow-left"
                    class="btn-outline btn-primary w-full"
                    wire:navigate
                />
            </div>
        @else
            <form wire:submit="sendResetLink" class="space-y-5">
                <x-mary-input
                    wire:model="email"
                    type="email"
                    label="{{ __('auth.forgot_password.email') }}"
                    placeholder="user@example.com"
                    icon="o-envelope"
                />

                <div class="pt-5 border-t border-base-content/10">
                    <x-mary-button
                        type="submit"
                        label="{{ __('passwords.send_reset_link') }}"
                        class="btn-primary w-full"
                        spinner="sendResetLink"
                    />
                </div>
            </form>

            <div class="mt-5 text-center">
                <a href="{{ route('login') }}" class="inline-flex items-center text-xs text-base-content/50 hover:text-primary transition-colors" wire:navigate>
                    <x-mary-icon name="o-arrow-left" class="size-3 mr-1.5" />
                    {{ __('auth.login.back_to_login') }}
                </a>
            </div>
        @endif
    </div>
</div>
