<div>
    <div class="bg-base-100 border-base-content/10 rounded-xl border p-6">
        <div class="mb-6 text-center">
            <h2 class="text-lg font-bold">{{ __('auth.login.title') }}</h2>
            <p class="text-base-content/50 mt-1 text-sm">{{ __('auth.login.subtitle') }}</p>
        </div>

        <form wire:submit="login" class="space-y-5">
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

            <div class="flex flex-col gap-2">
                <div class="flex items-center justify-between">
                    <x-ts-checkbox
                        wire:model="form.remember"
                        :label="__('auth.login.remember')"
                        class="checkbox-primary checkbox-sm rounded"
                    />

                    <a
                        href="{{ route('password.request') }}"
                        class="text-base-content/50 hover:text-primary text-xs transition-colors"
                        wire:navigate
                    >
                        {{ __('auth.login.forgot_password') }}
                    </a>
                </div>

                <div class="text-right">
                    <a
                        href="{{ route('recover.account') }}"
                        class="text-base-content/50 hover:text-primary text-xs transition-colors"
                        wire:navigate
                    >
                        {{ __('auth.login.recover_account') }}
                    </a>
                </div>
            </div>

            <div class="border-base-content/10 border-t pt-5">
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

    <div class="border-base-content/10 mt-5 border-t pt-5 text-center">
        <p class="text-base-content/50 text-xs">{{ __('auth.login.no_account') }}</p>
        <a
            href="{{ route('activate') }}"
            class="text-primary hover:text-primary-focus mt-0.5 inline-flex items-center text-xs font-medium transition-colors"
            wire:navigate
        >
            <x-ts-icon name="rocket-launch" class="mr-1 size-3" />
            {{ __('auth.login.claim_account') }}
        </a>
    </div>
</div>
