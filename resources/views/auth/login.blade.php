<div class="w-full animate-in fade-in slide-in-from-bottom-8 duration-1000">
    <x-mary-card shadow class="card-enterprise !bg-base-100 shadow-2xl shadow-base-content/5 border border-base-content/5 overflow-visible p-6 md:p-8">
        <div class="text-center mb-12 relative group">
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 size-32 bg-primary/5 rounded-full blur-2xl transition-all duration-700 group-hover:bg-primary/10"></div>
            <h2 class="text-4xl md:text-5xl font-black tracking-tightest text-base-content relative z-10">{{ __('auth.login.title') ?? 'Sign in' }}</h2>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-base-content/40 mt-4 relative z-10">{{ __('auth.login.subtitle') ?? 'Secure Access to Internara Gateway' }}</p>
        </div>

        <form wire:submit="login" class="space-y-8 relative z-10">
            <!-- Identifier -->
            <x-mary-input
                wire:model="identifier"
                label="{{ __('auth.login.identifier') ?? 'Identity' }}"
                placeholder="Username or Email"
                icon="o-identification"
                class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 transition-all duration-300 py-3 h-14 bg-base-200/50 focus:bg-base-100"
            />

            <!-- Password -->
            <x-mary-password
                wire:model="password"
                label="{{ __('auth.login.password') ?? 'Passkey' }}"
                placeholder="Your secure password"
                icon="o-key"
                right
                class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 transition-all duration-300 py-3 h-14 bg-base-200/50 focus:bg-base-100"
            />

            <!-- Remember Me & Forgot Password -->
            <div class="flex items-center justify-between px-2">
                <x-mary-checkbox
                    wire:model="remember"
                    label="{{ __('auth.login.remember') ?? 'Remember' }}"
                    class="checkbox-primary checkbox-sm rounded-lg"
                />

                <a href="{{ route('password.request') }}" class="text-[10px] font-black uppercase tracking-widest text-base-content/40 hover:text-primary transition-colors" wire:navigate>
                    {{ __('auth.login.forgot_password') ?? 'Reset Password' }}
                </a>
            </div>

            <!-- Submit -->
            <div class="pt-6 border-t border-base-content/5">
                <x-mary-button
                    type="submit"
                    label="{{ __('auth.login.submit') ?? 'Verify & Enter' }}"
                    class="btn-primary w-full h-16 rounded-[2rem] font-black uppercase tracking-[0.2em] text-xs shadow-2xl shadow-primary/30 hover:scale-[1.02] active:scale-[0.98] transition-all"
                    spinner="login"
                />
            </div>
        </form>
    </x-mary-card>

    <div class="text-center mt-12">
        <p class="text-[9px] text-base-content/20 font-black uppercase tracking-[0.4em] flex items-center justify-center gap-4">
            <span class="flex items-center gap-1"><x-mary-icon name="o-shield-check" class="size-3" /> S1 SECURE</span>
            <span class="size-1 rounded-full bg-base-content/10"></span>
            <span>AES-256</span>
            <span class="size-1 rounded-full bg-base-content/10"></span>
            <span>OAUTH READY</span>
        </p>
    </div>
</div>
