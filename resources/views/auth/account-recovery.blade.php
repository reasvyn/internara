<div>
    <div class="bg-base-100 border border-base-content/10 rounded-xl p-6">
        <div class="text-center mb-6">
            <h2 class="text-lg font-bold">{{ __('auth.account_recovery.title') }}</h2>
            <p class="text-sm text-base-content/50 mt-1">{{ __('auth.account_recovery.subtitle') }}</p>
        </div>

        <form wire:submit="redeem" class="space-y-5">
            <x-mary-input
                wire:model="form.username"
                :label="__('auth.account_recovery.username')"
                :placeholder="__('auth.account_recovery.username_placeholder')"
                icon="o-user"
            />

            <x-mary-input
                wire:model="form.recoveryCode"
                :label="__('auth.account_recovery.recovery_code')"
                :placeholder="__('auth.account_recovery.recovery_code_placeholder')"
                icon="o-key"
                class="font-mono tracking-widest"
            />

            <x-mary-password
                wire:model="form.password"
                label="{{ __('auth.account_recovery.new_password') }}"
                placeholder="••••••••"
                icon="o-lock-closed"
                right
            />

            <x-mary-password
                wire:model="form.password_confirmation"
                label="{{ __('auth.account_recovery.confirm_password') }}"
                placeholder="••••••••"
                icon="o-shield-check"
                right
            />

            <div class="pt-5 border-t border-base-content/10">
                <x-mary-button
                    type="submit"
                    label="{{ __('auth.account_recovery.submit') }}"
                    class="btn-primary w-full"
                    spinner="redeem"
                />
            </div>
        </form>

        <div class="mt-5 text-center">
            <a href="{{ route('login') }}" class="inline-flex items-center text-xs text-base-content/50 hover:text-primary transition-colors" wire:navigate>
                <x-mary-icon name="o-arrow-left" class="size-3 mr-1.5" />
                {{ __('auth.account_recovery.back_to_login') }}
            </a>
        </div>
    </div>
</div>
