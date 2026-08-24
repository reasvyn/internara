<div>
    <div class="bg-base-100 border-base-content/10 rounded-xl border p-6">
        <div class="mb-6 text-center">
            <h2 class="text-lg font-bold">{{ __('passwords.reset_password_title') }}</h2>
            <p class="text-base-content/50 mt-1 text-sm">{{ __('auth.reset_password.subtitle') }}</p>
        </div>

        <form wire:submit="resetPassword" class="space-y-5">
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

            <div class="border-base-content/10 border-t pt-5">
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
