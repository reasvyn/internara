<div>
    <div class="bg-base-100 border-base-content/10 rounded-lg border p-5 shadow-xs sm:p-6">
        <div class="mb-4 text-center sm:mb-5">
            <h2 class="text-base font-bold sm:text-lg">{{ __('passwords.reset_password_title') }}</h2>
            <p class="text-base-content/60 mt-0.5 text-xs sm:text-sm">{{ __('auth.forgot_password.subtitle') }}</p>
        </div>

        @if ($linkSent)
            <div class="bg-success/5 border-success/20 rounded-md border p-5 text-center sm:p-6">
                <div class="bg-success/10 text-success mx-auto mb-3 flex size-10 items-center justify-center rounded-md sm:size-12">
                    <x-ts-icon name="check-circle" class="size-5 sm:size-6" />
                </div>
                <h3 class="text-success mb-1.5 text-sm font-semibold sm:text-base">{{ __('passwords.sent') }}</h3>
                <p class="text-success/70 mb-5 text-xs">{{ __('passwords.sent_detail') }}</p>
                <x-ts-button
                    href="{{ route('login') }}"
                    :text="__('auth.login.back_to_login')"
                    icon="arrow-left"
                    class="w-full"
                    color="primary"
                    outline
                    wire:navigate
                />
            </div>
        @else
            <form wire:submit="sendResetLink" class="space-y-3.5 sm:space-y-4">
                <x-ts-input
                    wire:model="form.email"
                    type="email"
                    :label="__('auth.forgot_password.email')"
                    :placeholder="__('auth.forgot_password.email_placeholder')"
                    icon="envelope"
                />

                <div class="border-base-content/10 border-t pt-4">
                    <x-ts-button
                        type="submit"
                        :text="__('passwords.send_reset_link')"
                        class="w-full"
                        color="primary"
                        loading="sendResetLink"
                    />
                </div>
            </form>

            <div class="mt-4 text-center">
                <a
                    href="{{ route('login') }}"
                    class="text-base-content/60 hover:text-primary inline-flex items-center text-xs transition-colors"
                    wire:navigate
                >
                    <x-ts-icon name="arrow-left" class="mr-1.5 size-3" />
                    {{ __('auth.login.back_to_login') }}
                </a>
            </div>
        @endif
    </div>
</div>
