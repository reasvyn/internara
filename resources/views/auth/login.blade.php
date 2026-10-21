<div>
    <div class="bg-base-100 border-base-content/10 rounded-xl border p-5 shadow-xs sm:p-6">
        <div class="mb-4 text-center sm:mb-5">
            <h2 class="text-base font-bold sm:text-lg">{{ __('auth.login.title') }}</h2>
            <p class="text-base-content/60 mt-0.5 text-xs sm:text-sm">{{ __('auth.login.subtitle') }}</p>
        </div>

        <form wire:submit="login" class="space-y-3.5 sm:space-y-4">
            <x-ts-input
                wire:model="form.identifier"
                :label="__('auth.login.identifier')"
                :placeholder="__('auth.login.identifier_placeholder')"
                icon="identification"
            />

            <x-ts-input
                wire:model="form.password"
                :label="__('auth.login.password')"
                placeholder="••••••••"
                icon="key"
                type="password"
            />

            <div class="flex flex-col gap-1.5 pt-0.5">
                <div class="flex items-center justify-between">
                    <x-ts-checkbox
                        wire:model="form.remember"
                        :label="__('auth.login.remember')"
                        class="checkbox-primary checkbox-sm rounded"
                    />

                    <a
                        href="{{ route('password.request') }}"
                        class="text-base-content/60 hover:text-primary text-xs transition-colors"
                        wire:navigate
                    >
                        {{ __('auth.login.forgot_password') }}
                    </a>
                </div>

                <div class="text-right">
                    <a
                        href="{{ route('recover.account') }}"
                        class="text-base-content/60 hover:text-primary text-xs transition-colors"
                        wire:navigate
                    >
                        {{ __('auth.login.recover_account') }}
                    </a>
                </div>
            </div>

            <div class="border-base-content/10 border-t pt-4">
                <x-ts-button
                    type="submit"
                    :text="__('auth.login.submit')"
                    class="w-full"
                    color="primary"
                    loading="login"
                />
            </div>
        </form>
    </div>

    <div class="border-base-content/10 mt-4 border-t pt-4 text-center">
        <p class="text-base-content/60 text-xs">{{ __('auth.login.no_account') }}</p>
        <a
            href="{{ route('activate') }}"
            class="text-primary hover:text-primary/80 mt-1 inline-flex items-center text-xs font-semibold transition-colors"
            wire:navigate
        >
            <x-ts-icon name="rocket-launch" class="mr-1 size-3.5" />
            {{ __('auth.login.claim_account') }}
        </a>
    </div>
</div>
