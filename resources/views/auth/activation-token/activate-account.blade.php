<div>
    <div class="bg-base-100 border-base-content/10 rounded-xl border p-6">
        <div class="mb-6 text-center">
            <h2 class="text-lg font-bold">{{ __('auth.activate.title') }}</h2>
            <p class="text-base-content/50 mt-1 text-sm">{{ __('auth.activate.subtitle') }}</p>
        </div>

        <form wire:submit="activate" class="space-y-5">
            <x-ts-input
                wire:model="email"
                type="email"
                :label="__('auth.activate.email')"
                :placeholder="__('auth.activate.email_placeholder')"
                icon="envelope"
            />

            <x-ts-input
                wire:model="code"
                :label="__('auth.activate.code')"
                :placeholder="__('auth.activate.code_placeholder')"
                icon="hashtag"
                maxlength="19"
            />

            <x-ts-password
                wire:model="password"
                :label="__('auth.activate.password')"
                placeholder="••••••••"
                icon="key"
                right
            />

            <x-ts-password
                wire:model="password_confirmation"
                :label="__('auth.activate.password_confirmation')"
                placeholder="••••••••"
                icon="key"
                right
            />

            <div class="border-base-content/10 border-t pt-5">
                <x-ts-button
                    type="submit"
                    :text="__('auth.activate.submit')"
                    class="w-full"
                    color="primary"
                    loading="activate"
                />
            </div>
        </form>

        <div class="mt-5 text-center">
            <a
                href="{{ route('login') }}"
                class="text-base-content/50 hover:text-primary text-xs transition-colors"
                wire:navigate
            >
                {{ __('auth.activate.back_to_login') }}
            </a>
        </div>
    </div>
</div>
