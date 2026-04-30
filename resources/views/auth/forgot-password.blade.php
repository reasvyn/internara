<div>
    <x-mary-card class="mx-auto w-full max-w-md">
        <x-slot:title>
            <h2 class="text-center text-2xl font-bold text-base-content">{{ __('passwords.reset_password_title') ?? 'Reset your password' }}</h2>
        </x-slot:title>

        @if ($linkSent)
            <x-mary-alert
                type="success"
                :title="__('passwords.sent')"
                :description="__('passwords.sent_detail') ?? 'We have emailed your password reset link.'"
                icon="o-envelope"
                class="mb-4"
            />
            <div class="text-center">
                <a href="{{ route('login') }}" class="text-sm font-medium text-primary" wire:navigate>
                    {{ __('auth::ui.login.back_to_login') ?? 'Back to login' }}
                </a>
            </div>
        @else
            <form wire:submit="sendResetLink" class="space-y-6">
                <x-mary-input
                    wire:model="email"
                    type="email"
                    :label="__('auth::ui.forgot_password.form.email') ?? 'Email address'"
                    placeholder="user@example.com"
                    icon="o-envelope"
                />

                <x-mary-button
                    type="submit"
                    label="{{ __('passwords.send_reset_link') ?? 'Send reset link' }}"
                    class="btn-primary w-full"
                    spinner="sendResetLink"
                />
            </form>

            <div class="mt-4 text-center">
                <a href="{{ route('login') }}" class="text-sm font-medium text-primary" wire:navigate>
                    {{ __('auth::ui.login.back_to_login') ?? 'Back to login' }}
                </a>
            </div>
        @endif
    </x-mary-card>
</div>
