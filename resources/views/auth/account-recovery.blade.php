<div class="w-full animate-in fade-in slide-in-from-bottom-8 duration-1000">
    <x-mary-card shadow class="card-enterprise !bg-base-100 shadow-2xl shadow-base-content/5 border border-base-content/5 overflow-visible p-6 md:p-8">
        <div class="text-center mb-10 relative group">
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 size-24 bg-primary/5 rounded-full blur-2xl transition-all duration-700 group-hover:bg-primary/10"></div>
            <h2 class="text-3xl md:text-4xl font-black tracking-tightest text-base-content relative z-10">Account Recovery</h2>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-base-content/40 mt-4 relative z-10">Redeem your credential slip</p>
        </div>

        <form wire:submit="redeem" class="space-y-6 relative z-10">
            <x-mary-input
                wire:model="username"
                label="Username"
                placeholder="Your username"
                icon="o-user"
                class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 transition-all duration-300 py-3 h-14 bg-base-200/50 focus:bg-base-100"
            />

            <x-mary-input
                wire:model="recoveryCode"
                label="Recovery Code"
                placeholder="e.g. ABC2X5K9M7P1"
                icon="o-key"
                class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 transition-all duration-300 py-3 h-14 bg-base-200/50 focus:bg-base-100 font-mono tracking-widest"
            />

            <x-mary-password
                wire:model="password"
                label="New Password"
                placeholder="Min 8 characters"
                icon="o-lock-closed"
                right
                class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 transition-all duration-300 py-3 h-14 bg-base-200/50 focus:bg-base-100"
            />

            <x-mary-password
                wire:model="password_confirmation"
                label="Confirm New Password"
                placeholder="Repeat your password"
                icon="o-shield-check"
                right
                class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 transition-all duration-300 py-3 h-14 bg-base-200/50 focus:bg-base-100"
            />

            <div class="pt-6 border-t border-base-content/5">
                <x-mary-button
                    type="submit"
                    label="Recover Account"
                    class="btn-primary w-full h-16 rounded-[2rem] font-black uppercase tracking-[0.2em] text-xs shadow-2xl shadow-primary/30 hover:scale-[1.02] transition-all"
                    spinner="redeem"
                />
            </div>
        </form>

        <div class="mt-8 text-center">
            <a href="{{ route('login') }}" class="inline-flex items-center text-[10px] font-black uppercase tracking-[0.2em] text-base-content/40 hover:text-primary transition-colors" wire:navigate>
                <x-mary-icon name="o-arrow-left" class="size-3 mr-2" />
                Back to Login
            </a>
        </div>
    </x-mary-card>
</div>
