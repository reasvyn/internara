<div>
    <div class="bg-base-100 border border-base-content/10 rounded-xl p-6">
        <div class="text-center mb-6">
            <h2 class="text-lg font-bold">{{ __('auth.activate.title') }}</h2>
            <p class="text-sm text-base-content/50 mt-1">{{ __('auth.activate.subtitle') }}</p>
        </div>

        <form wire:submit="activate" class="space-y-5">
            <x-mary-input
                wire:model="email"
                type="email"
                :label="__('auth.activate.email')"
                :placeholder="__('auth.activate.email_placeholder')"
                icon="o-envelope"
            />

            <x-mary-input
                wire:model="code"
                :label="__('auth.activate.code')"
                :placeholder="__('auth.activate.code_placeholder')"
                icon="o-hashtag"
                maxlength="6"
            />

            <x-mary-password
                wire:model="password"
                :label="__('auth.activate.password')"
                placeholder="••••••••"
                icon="o-key"
                right
            />

            <x-mary-password
                wire:model="password_confirmation"
                :label="__('auth.activate.password_confirmation')"
                placeholder="••••••••"
                icon="o-key"
                right
            />

            <div class="pt-5 border-t border-base-content/10">
                <x-mary-button
                    type="submit"
                    :label="__('auth.activate.submit')"
                    class="btn-primary w-full"
                    spinner="activate"
                />
            </div>
        </form>

        <div class="mt-5 text-center">
            <a href="{{ route('login') }}" class="text-xs text-base-content/50 hover:text-primary transition-colors" wire:navigate>
                {{ __('auth.activate.back_to_login') }}
            </a>
        </div>
    </div>
</div>
