<div>
    <div class="bg-base-100 border-base-content/10 rounded-xl border p-6">
        <div class="mb-6 text-center">
            <h2 class="text-lg font-bold">{{ __('passwords.reset_password_title') }}</h2>
            <p class="text-base-content/50 mt-1 text-sm">{{ __('auth.forgot_password.subtitle') }}</p>
        </div>

        @if ($linkSent)
            <div class="bg-success/5 border-success/20 rounded-xl border p-8 text-center">
                <div class="bg-success/10 text-success mx-auto mb-4 flex size-12 items-center justify-center rounded-full">
                    <x-ts-icon name="check-circle" class="size-6" />
                </div>
                <h3 class="text-success mb-2 font-semibold">{{ __('passwords.sent') }}</h3>
                <p class="text-success/70 mb-6 text-xs">{{ __('passwords.sent_detail') }}</p>
                <x-ts-button
                    href="{{ route('login') }}"
                    :text="__('auth.login.back_to_login')"
                    icon="arrow-left"
                    class="btn-outline w-full"
                    color="primary"
                    wire:navigate
                />
            </div>
        @else
            <form wire:submit="sendResetLink" class="space-y-5">
                <x-ts-input
                    wire:model="form.email"
                    type="email"
                    :label="__('auth.forgot_password.email')"
                    :placeholder="__('auth.forgot_password.email_placeholder')"
                    icon="envelope"
                />

                <div class="border-base-content/10 border-t pt-5">
                    <x-ts-button
                        type="submit"
                        :text="__('passwords.send_reset_link')"
                        class="w-full"
                        color="primary"
                        loading="sendResetLink"
                    />
                </div>
            </form>

            <div class="mt-5 text-center">
                <a
                    href="{{ route('login') }}"
                    class="text-base-content/50 hover:text-primary inline-flex items-center text-xs transition-colors"
                    wire:navigate
                >
                    <x-ts-icon name="arrow-left" class="mr-1.5 size-3" />
                    {{ __('auth.login.back_to_login') }}
                </a>
            </div>
        @endif
    </div>
</div>
