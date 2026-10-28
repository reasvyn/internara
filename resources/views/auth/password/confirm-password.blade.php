<div>
    <div class="bg-base-100 border-base-content/10 rounded-lg border p-5 shadow-xs sm:p-6">
        <div class="mb-4 text-center sm:mb-5">
            <h1 class="text-base font-bold sm:text-lg">{{ __('auth.confirm_password.title') }}</h2>
            <p class="text-base-content/60 mt-0.5 text-xs sm:text-sm">{{ __('auth.confirm_password.subtitle') }}</p>
        </div>

        <form wire:submit="confirm" class="space-y-3.5 sm:space-y-4">
            <x-ts-password
                wire:model="form.password"
                label="{{ __('auth.confirm_password.password') }}"
                placeholder="••••••••"
                icon="key"
                right
            />

            <div class="border-base-content/10 border-t pt-4">
                <x-ts-button
                    type="submit"
                    text="{{ __('auth.confirm_password.confirm') }}"
                    class="w-full"
                    color="primary"
                    loading="confirm"
                />
            </div>
        </form>
    </div>
</div>
