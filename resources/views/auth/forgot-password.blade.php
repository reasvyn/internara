<div class="w-full animate-in fade-in slide-in-from-bottom-8 duration-1000">
    <x-mary-card shadow class="card-enterprise !bg-base-100 shadow-2xl shadow-base-content/5 border border-base-content/5 overflow-visible p-6 md:p-8">
        <div class="text-center mb-10 relative group">
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 size-24 bg-primary/5 rounded-full blur-2xl transition-all duration-700 group-hover:bg-primary/10"></div>
            <h2 class="text-3xl md:text-4xl font-black tracking-tightest text-base-content relative z-10">{{ __('passwords.reset_password_title') ?? 'Reset Password' }}</h2>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-base-content/40 mt-4 relative z-10">{{ __('auth.forgot_password.subtitle') ?? 'Enter your email to receive a reset link' }}</p>
        </div>

        @if ($linkSent)
            <div class="bg-success/5 border border-success/10 p-8 rounded-[2rem] text-center relative overflow-hidden">
                <div class="absolute inset-0 bg-success/5 animate-pulse"></div>
                <div class="relative z-10">
                    <div class="size-20 rounded-3xl bg-success/20 text-success flex items-center justify-center mx-auto mb-6 shadow-lg shadow-success/20">
                        <x-mary-icon name="o-check-circle" class="size-10" />
                    </div>
                    <h3 class="text-xl font-black tracking-tight text-success mb-3">{{ __('passwords.sent') }}</h3>
                    <p class="text-xs font-bold text-success/70 leading-relaxed mb-8 uppercase tracking-widest">
                        {{ __('passwords.sent_detail') ?? 'We have emailed your password reset link.' }}
                    </p>
                    <x-mary-button 
                        link="{{ route('login') }}" 
                        label="{{ __('auth.login.back_to_login') ?? 'Back to login' }}" 
                        icon="o-arrow-left"
                        class="btn-success btn-outline w-full h-14 rounded-2xl font-black uppercase tracking-[0.2em] text-[10px]" 
                        wire:navigate 
                    />
                </div>
            </div>
        @else
            <form wire:submit="sendResetLink" class="space-y-8 relative z-10">
                <x-mary-input
                    wire:model="email"
                    type="email"
                    label="{{ __('auth.forgot_password.email') ?? 'Email address' }}"
                    placeholder="user@example.com"
                    icon="o-envelope"
                    class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 transition-all duration-300 py-3 h-14 bg-base-200/50 focus:bg-base-100"
                />

                <div class="pt-6 border-t border-base-content/5">
                    <x-mary-button
                        type="submit"
                        label="{{ __('passwords.send_reset_link') ?? 'Send reset link' }}"
                        class="btn-primary w-full h-16 rounded-[2rem] font-black uppercase tracking-[0.2em] text-xs shadow-2xl shadow-primary/30 hover:scale-[1.02] active:scale-[0.98] transition-all"
                        spinner="sendResetLink"
                    />
                </div>
            </form>

            <div class="mt-8 text-center relative z-10">
                <a href="{{ route('login') }}" class="inline-flex items-center text-[10px] font-black uppercase tracking-[0.2em] text-base-content/40 hover:text-primary transition-colors" wire:navigate>
                    <x-mary-icon name="o-arrow-left" class="size-3 mr-2" />
                    {{ __('auth.login.back_to_login') ?? 'Back to login' }}
                </a>
            </div>
        @endif
    </x-mary-card>
</div>
