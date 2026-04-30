<div>
    <x-mary-card class="mx-auto w-full max-w-md">
        <x-slot:title>
            <h2 class="text-center text-2xl font-bold text-base-content">{{ __('auth::ui.login.title') ?? 'Sign in to your account' }}</h2>
        </x-slot:title>

        <form wire:submit="login" class="space-y-6">
            <!-- Identifier -->
            <x-mary-input
                wire:model="identifier"
                :label="__('auth::ui.login.form.identifier') ?? 'Email or Username'"
                placeholder="user@example.com"
                icon="o-user"
            />

            <!-- Password -->
            <x-mary-password
                wire:model="password"
                :label="__('auth::ui.login.form.password') ?? 'Password'"
                placeholder="••••••••"
            />

            <!-- Remember Me & Forgot Password -->
            <div class="flex items-center justify-between">
                <x-mary-checkbox
                    wire:model="remember"
                    :label="__('auth::ui.login.form.remember') ?? 'Remember me'"
                />

                <a href="{{ route('password.request') }}" class="text-sm font-medium text-primary" wire:navigate>
                    {{ __('auth::ui.login.form.forgot_password') ?? 'Forgot password?' }}
                </a>
            </div>

            <!-- Submit -->
            <x-mary-button
                type="submit"
                label="{{ __('auth::ui.login.form.submit') ?? 'Sign in' }}"
                class="btn-primary w-full"
                spinner="login"
            />
        </form>
    </x-mary-card>
</div>
