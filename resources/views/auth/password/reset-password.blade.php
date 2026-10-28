<div>
    <div class="bg-base-100 border-base-content/10 rounded-lg border p-5 shadow-xs sm:p-6">
        <div class="mb-4 text-center sm:mb-5">
            <h2 class="text-base font-bold sm:text-lg">{{ __('passwords.reset_password_title') }}</h2>
            <p class="text-base-content/60 mt-0.5 text-xs sm:text-sm">{{ __('auth.reset_password.subtitle') }}</p>
        </div>

        <form wire:submit="resetPassword" class="space-y-3.5 sm:space-y-4">
            <x-ts-input
                wire:model="form.email"
                type="email"
                :label="__('auth.reset_password.email')"
                :placeholder="__('auth.reset_password.email_placeholder')"
                icon="envelope"
                readonly
            />

            <x-ts-password
                wire:model="form.password"
                :label="__('auth.reset_password.password')"
                placeholder="••••••••"
                icon="key"
                right
            />

            <x-ts-password
                wire:model="form.password_confirmation"
                :label="__('auth.reset_password.password_confirmation')"
                placeholder="••••••••"
                icon="key"
                right
            />

            <div class="border-base-content/10 border-t pt-4">
                <x-ts-button
                    type="submit"
                    :text="__('passwords.reset_password')"
                    class="w-full"
                    color="primary"
                    loading="resetPassword"
                />
            </div>
        </form>
    </div>
</div>
