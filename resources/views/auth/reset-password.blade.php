<div class="w-full">
    <div class="bg-base-100 p-8 rounded-[2rem] shadow-xl shadow-base-300/50 border border-base-200">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-black tracking-tight text-base-content">{{ __('passwords.reset_password_title') ?? 'New Password' }}</h2>
            <p class="text-sm text-base-content/50 mt-2">{{ __('auth::ui.reset_password.subtitle') ?? 'Create a strong new password for your account' }}</p>
        </div>

        <form wire:submit="resetPassword" class="space-y-5">
            <x-mary-input
                wire:model="email"
                type="email"
                label="{{ __('auth::ui.reset_password.form.email') ?? 'Email address' }}"
                placeholder="user@example.com"
                icon="o-envelope"
                class="rounded-xl border-base-300 focus:border-primary"
                readonly
            />

            <x-mary-password
                wire:model="password"
                label="{{ __('auth::ui.reset_password.form.password') ?? 'New password' }}"
                placeholder="••••••••"
                class="rounded-xl border-base-300 focus:border-primary"
            />

            <x-mary-password
                wire:model="password_confirmation"
                label="{{ __('auth::ui.reset_password.form.password_confirmation') ?? 'Confirm new password' }}"
                placeholder="••••••••"
                class="rounded-xl border-base-300 focus:border-primary"
            />

            <div class="pt-4">
                <x-mary-button
                    type="submit"
                    label="{{ __('passwords.reset_password') ?? 'Update Password' }}"
                    class="btn-primary w-full rounded-xl font-black uppercase tracking-widest shadow-lg shadow-primary/20"
                    spinner="resetPassword"
                />
            </div>
        </form>
    </div>

    <p class="text-center mt-8 text-xs text-base-content/40 font-medium uppercase tracking-[0.2em]">
        &copy; {{ date('Y') }} {{ App\Support\AppInfo::get('name', config('app.name')) }} &bull; S1 Secure Architecture
    </p>
</div>
