<div>
    <div class="bg-base-100 border-base-content/10 rounded-xl border p-6">
        <div class="mb-6 text-center">
            <h2 class="text-lg font-bold">{{ __('auth.account_recovery.title') }}</h2>
            <p class="text-base-content/50 mt-1 text-sm">{{ __('auth.account_recovery.subtitle') }}</p>
        </div>

        <form wire:submit="redeem" class="space-y-5">
            <x-ts-input
                wire:model="form.username"
                :label="__('auth.account_recovery.username')"
                :placeholder="__('auth.account_recovery.username_placeholder')"
                icon="user"
            />

            <x-ts-input
                wire:model="form.recoveryCode"
                :label="__('auth.account_recovery.recovery_code')"
                :placeholder="__('auth.account_recovery.recovery_code_placeholder')"
                icon="key"
                class="font-mono tracking-widest"
            />

            <x-ts-password
                wire:model="form.password"
                label="{{ __('auth.account_recovery.new_password') }}"
                placeholder="••••••••"
                icon="lock-closed"
                right
            />

            <x-ts-password
                wire:model="form.password_confirmation"
                label="{{ __('auth.account_recovery.confirm_password') }}"
                placeholder="••••••••"
                icon="shield-check"
                right
            />

            <div class="border-base-content/10 border-t pt-5">
                <x-ts-button
                    type="submit"
                    text="{{ __('auth.account_recovery.submit') }}"
                    class="w-full"
                    color="primary"
                    loading="redeem"
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
                {{ __('auth.account_recovery.back_to_login') }}
            </a>
        </div>
    </div>
</div>
