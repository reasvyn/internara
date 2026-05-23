<div>
    <div class="bg-base-100 border border-base-content/10 rounded-xl p-6">
        <div class="text-center mb-6">
            <h2 class="text-lg font-bold">{{ __('auth.login.title') }}</h2>
            <p class="text-sm text-base-content/50 mt-1">{{ __('auth.login.subtitle') }}</p>
        </div>

        <form wire:submit="login" class="space-y-5">
            <x-mary-input
                wire:model="form.identifier"
                label="{{ __('auth.login.identifier') }}"
                placeholder="username@email.com"
                icon="o-identification"
            />

            <x-mary-password
                wire:model="form.password"
                label="{{ __('auth.login.password') }}"
                placeholder="••••••••"
                icon="o-key"
                right
            />

            <div class="flex items-center justify-between">
                <x-mary-checkbox
                    wire:model="form.remember"
                    label="{{ __('auth.login.remember') }}"
                    class="checkbox-primary checkbox-sm rounded"
                />

                <a href="{{ route('password.request') }}" class="text-xs text-base-content/50 hover:text-primary transition-colors" wire:navigate>
                    {{ __('auth.login.forgot_password') }}
                </a>
            </div>

            <div class="pt-5 border-t border-base-content/10">
                <x-mary-button
                    type="submit"
                    label="{{ __('auth.login.submit') }}"
                    class="btn-primary w-full"
                    spinner="login"
                />
            </div>
        </form>
    </div>
</div>
