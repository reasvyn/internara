<div>
    <x-mary-card class="mx-auto w-full max-w-md">
        <x-slot:title>
            <h2 class="text-center text-2xl font-bold text-base-content">{{ __('passwords.reset_password_title') ?? 'Reset your password' }}</h2>
        </x-slot:title>

        <form wire:submit="resetPassword" class="space-y-6">
            <x-mary-input
                wire:model="email"
                type="email"
                :label="__('auth::ui.reset_password.form.email') ?? 'Email address'"
                placeholder="user@example.com"
                icon="o-envelope"
            />

            <x-mary-password
                wire:model="password"
                :label="__('auth::ui.reset_password.form.password') ?? 'New password'"
                placeholder="••••••••"
            />

            <x-mary-password
                wire:model="password_confirmation"
                :label="__('auth::ui.reset_password.form.password_confirmation') ?? 'Confirm new password'"
                placeholder="••••••••"
            />

            <x-mary-button
                type="submit"
                label="{{ __('passwords.reset_password') ?? 'Reset password' }}"
                class="btn-primary w-full"
                spinner="resetPassword"
            />
        </form>
    </x-mary-card>
</div>
