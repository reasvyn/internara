<div>
    <div class="bg-base-100 border-base-content/10 rounded-xl border p-6">
        <div class="mb-6 text-center">
            <h2 class="text-lg font-bold">{{ __('auth.confirm_password.title') }}</h2>
            <p class="text-base-content/50 mt-1 text-sm">{{ __('auth.confirm_password.subtitle') }}</p>
        </div>

        <form wire:submit="confirm" class="space-y-5">
            <x-ts-password
                wire:model="form.password"
                label="{{ __('auth.confirm_password.password') }}"
                placeholder="••••••••"
                icon="key"
                right
            />

            <div class="border-base-content/10 border-t pt-5">
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
