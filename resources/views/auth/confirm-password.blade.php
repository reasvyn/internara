<div>
    <div class="bg-base-100 border border-base-content/10 rounded-xl p-6">
        <div class="text-center mb-6">
            <h2 class="text-lg font-bold">{{ __('auth.confirm_password.title') }}</h2>
            <p class="text-sm text-base-content/50 mt-1">{{ __('auth.confirm_password.subtitle') }}</p>
        </div>

        <form wire:submit="confirm" class="space-y-5">
            <x-mary-password
                wire:model="password"
                label="{{ __('auth.confirm_password.password') }}"
                placeholder="••••••••"
                icon="o-key"
                right
            />

            <div class="pt-5 border-t border-base-content/10">
                <x-mary-button
                    type="submit"
                    label="{{ __('auth.confirm_password.confirm') }}"
                    class="btn-primary w-full"
                    spinner="confirm"
                />
            </div>
        </form>
    </div>
</div>
