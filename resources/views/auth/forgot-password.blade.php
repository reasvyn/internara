<div class="w-full">
    <div class="bg-base-100 p-8 rounded-[2rem] shadow-xl shadow-base-300/50 border border-base-200">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-black tracking-tight text-base-content">{{ __('passwords.reset_password_title') ?? 'Reset Password' }}</h2>
            <p class="text-sm text-base-content/50 mt-2">{{ __('auth::ui.forgot_password.subtitle') ?? 'Enter your email to receive a reset link' }}</p>
        </div>

        @if ($linkSent)
            <div class="bg-success/5 border border-success/10 p-6 rounded-2xl text-center">
                <div class="size-16 rounded-2xl bg-success/20 text-success flex items-center justify-center mx-auto mb-4">
                    <x-mary-icon name="o-check-circle" class="size-10" />
                </div>
                <h3 class="text-lg font-bold text-success mb-2">{{ __('passwords.sent') }}</h3>
                <p class="text-sm text-success/70 leading-relaxed mb-6">
                    {{ __('passwords.sent_detail') ?? 'We have emailed your password reset link.' }}
                </p>
                <x-mary-button 
                    link="{{ route('login') }}" 
                    label="{{ __('auth::ui.login.back_to_login') ?? 'Back to login' }}" 
                    class="btn-ghost btn-sm font-bold uppercase tracking-widest" 
                    wire:navigate 
                />
            </div>
        @else
            <form wire:submit="sendResetLink" class="space-y-6">
                <x-mary-input
                    wire:model="email"
                    type="email"
                    label="{{ __('auth::ui.forgot_password.form.email') ?? 'Email address' }}"
                    placeholder="user@example.com"
                    icon="o-envelope"
                    class="rounded-xl border-base-300 focus:border-primary"
                />

                <div class="pt-2">
                    <x-mary-button
                        type="submit"
                        label="{{ __('passwords.send_reset_link') ?? 'Send reset link' }}"
                        class="btn-primary w-full rounded-xl font-black uppercase tracking-widest shadow-lg shadow-primary/20"
                        spinner="sendResetLink"
                    />
                </div>
            </form>

            <div class="mt-8 text-center">
                <a href="{{ route('login') }}" class="text-xs font-bold uppercase tracking-widest text-primary hover:text-primary-focus transition-colors" wire:navigate>
                    <x-mary-icon name="o-arrow-left" class="size-3 mr-1" />
                    {{ __('auth::ui.login.back_to_login') ?? 'Back to login' }}
                </a>
            </div>
        @endif
    </div>

    <p class="text-center mt-8 text-xs text-base-content/40 font-medium uppercase tracking-[0.2em]">
        &copy; {{ date('Y') }} {{ App\Support\AppInfo::get('name', config('app.name')) }} &bull; S1 Secure Architecture
    </p>
</div>
