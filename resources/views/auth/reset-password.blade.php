<div class="w-full animate-in fade-in slide-in-from-bottom-8 duration-1000">
    <x-mary-card shadow class="card-enterprise !bg-base-100 shadow-2xl shadow-base-content/5 border border-base-content/5 overflow-visible p-6 md:p-8">
        <div class="text-center mb-10 relative group">
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 size-24 bg-primary/5 rounded-full blur-2xl transition-all duration-700 group-hover:bg-primary/10"></div>
            <h2 class="text-3xl md:text-4xl font-black tracking-tightest text-base-content relative z-10">{{ __('passwords.reset_password_title') ?? 'New Password' }}</h2>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-base-content/40 mt-4 relative z-10">{{ __('auth.reset_password.subtitle') ?? 'Create a strong new password for your account' }}</p>
        </div>

        <form wire:submit="resetPassword" class="space-y-6 relative z-10">
            <x-mary-input
                wire:model="email"
                type="email"
                label="{{ __('auth.reset_password.email') ?? 'Email address' }}"
                placeholder="user@example.com"
                icon="o-envelope"
                class="rounded-[1.5rem] border-base-content/5 bg-base-200/30 text-base-content/50 py-3 h-14"
                readonly
            />

            <x-mary-password
                wire:model="password"
                label="{{ __('auth.reset_password.password') ?? 'New password' }}"
                placeholder="••••••••"
                class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 transition-all duration-300 py-3 h-14 bg-base-200/50 focus:bg-base-100"
            />

            <x-mary-password
                wire:model="password_confirmation"
                label="{{ __('auth.reset_password.password_confirmation') ?? 'Confirm new password' }}"
                placeholder="••••••••"
                class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 transition-all duration-300 py-3 h-14 bg-base-200/50 focus:bg-base-100"
            />

            <div class="pt-6 border-t border-base-content/5 mt-8">
                <x-mary-button
                    type="submit"
                    label="{{ __('passwords.reset_password') ?? 'Update Password' }}"
                    class="btn-primary w-full h-16 rounded-[2rem] font-black uppercase tracking-[0.2em] text-xs shadow-2xl shadow-primary/30 hover:scale-[1.02] active:scale-[0.98] transition-all"
                    spinner="resetPassword"
                />
            </div>
        </form>
    </x-mary-card>
</div>
