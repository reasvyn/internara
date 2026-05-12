<div>
    <div class="bg-base-100 border border-base-content/10 rounded-xl p-6">
        <div class="text-center mb-6">
            <h2 class="text-lg font-bold">{{ __('passwords.reset_password_title') }}</h2>
            <p class="text-sm text-base-content/50 mt-1">{{ __('auth.reset_password.subtitle') }}</p>
        </div>

        <form wire:submit="resetPassword" class="space-y-5">
            <x-mary-input
                wire:model="email"
                type="email"
                label="{{ __('auth.reset_password.email') }}"
                placeholder="user@example.com"
                icon="o-envelope"
                readonly
            />

            <x-mary-password
                wire:model="password"
                label="{{ __('auth.reset_password.password') }}"
                placeholder="••••••••"
            />

            <x-mary-password
                wire:model="password_confirmation"
                label="{{ __('auth.reset_password.password_confirmation') }}"
                placeholder="••••••••"
            />

            <div class="pt-5 border-t border-base-content/10">
                <x-mary-button
                    type="submit"
                    label="{{ __('passwords.reset_password') }}"
                    class="btn-primary w-full"
                    spinner="resetPassword"
                />
            </div>
        </form>
    </div>
</div>
