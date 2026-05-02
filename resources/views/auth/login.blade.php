<div class="w-full">
    <x-mary-card shadow class="card-enterprise p-4">
        <div class="text-center mb-10">
            <h2 class="text-4xl font-black tracking-tighter text-base-content">{{ __('auth::ui.login.title') ?? 'Sign in' }}</h2>
            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-base-content/30 mt-3">{{ __('auth::ui.login.subtitle') ?? 'Secure Access to Internara Gateway' }}</p>
        </div>

        <form wire:submit="login" class="space-y-6">
            <!-- Identifier -->
            <x-mary-input
                wire:model="identifier"
                label="{{ __('auth::ui.login.form.identifier') ?? 'Identity' }}"
                placeholder="Username or Email"
                icon="o-identification"
                class="rounded-2xl border-base-content/10 focus:border-primary transition-all duration-300"
            />

            <!-- Password -->
            <x-mary-password
                wire:model="password"
                label="{{ __('auth::ui.login.form.password') ?? 'Passkey' }}"
                placeholder="Your secure password"
                icon="o-key"
                class="rounded-2xl border-base-content/10 focus:border-primary transition-all duration-300"
            />

            <!-- Remember Me & Forgot Password -->
            <div class="flex items-center justify-between px-1">
                <x-mary-checkbox
                    wire:model="remember"
                    label="{{ __('auth::ui.login.form.remember') ?? 'Remember' }}"
                    class="checkbox-primary rounded-lg"
                />

                <a href="{{ route('password.request') }}" class="text-[10px] font-black uppercase tracking-widest text-primary hover:text-primary/70 transition-all underline decoration-2 underline-offset-4" wire:navigate>
                    {{ __('auth::ui.login.form.forgot_password') ?? 'Reset' }}
                </a>
            </div>

            <!-- Submit -->
            <div class="pt-4">
                <x-mary-button
                    type="submit"
                    label="{{ __('auth::ui.login.form.submit') ?? 'Verify & Enter' }}"
                    class="btn-primary w-full h-14 rounded-2xl font-black uppercase tracking-widest shadow-xl shadow-primary/20 hover:scale-[1.02] active:scale-[0.98] transition-all"
                    spinner="login"
                />
            </div>
        </form>
    </x-mary-card>

    <div class="text-center mt-10">
        <p class="text-[10px] text-base-content/20 font-black uppercase tracking-[0.3em] flex items-center justify-center gap-3">
            <span>S1 SECURE</span>
            <span class="size-1 rounded-full bg-base-content/10"></span>
            <span>AES-256</span>
            <span class="size-1 rounded-full bg-base-content/10"></span>
            <span>MFA READY</span>
        </p>
    </div>
</div>
