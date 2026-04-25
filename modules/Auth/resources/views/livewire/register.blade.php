<x-ui::card
    wire:key="register-card"
    class="w-full max-w-lg text-center"
    :title="__('auth::ui.register.title')"
    :subtitle="__('auth::ui.register.subtitle')"
>
    <x-slot:header>
        <div class="mb-4 flex justify-center">
            <x-ui::badge 
                variant="primary" 
                class="px-4 py-1.5 rounded-full font-bold tracking-wide uppercase text-[10px]"
                :value="__('auth::ui.register.student_only_badge')" 
            />
        </div>
    </x-slot:header>

    <x-ui::form class="text-start" wire:submit="register">
        <div wire:key="reg-name">
            <x-ui::input
                type="text"
                :label="__('auth::ui.register.form.name')"
                :placeholder="__('auth::ui.register.form.name_placeholder')"
                wire:model="form.name"
                required
            />
        </div>
        <div wire:key="reg-email">
            <x-ui::input
                type="email"
                :label="__('auth::ui.register.form.email')"
                :placeholder="__('auth::ui.register.form.email_placeholder')"
                wire:model="form.email"
                required
            />
        </div>
        <div wire:key="reg-password">
            <x-ui::input
                type="password"
                :label="__('auth::ui.register.form.password')"
                :placeholder="__('auth::ui.register.form.password_placeholder')"
                wire:model.live="form.password"
                required
            />
            <x-ui::password-strength :password="$form->password" />
        </div>
        <div wire:key="reg-password-conf">
            <x-ui::input
                type="password"
                :label="__('auth::ui.register.form.password_confirmation')"
                :placeholder="__('auth::ui.register.form.password_confirmation_placeholder')"
                wire:model="form.password_confirmation"
                required
            />
        </div>

        <div wire:key="reg-captcha">
            @if(config('services.cloudflare.turnstile.site_key'))
                <x-ui::turnstile fieldName="captcha_token" />
            @endif
        </div>

        <div class="mt-4 flex flex-col gap-8" wire:key="reg-actions">
            <div class="w-full space-y-2">
                <x-ui::button
                    variant="primary"
                    class="w-full"
                    :label="__('auth::ui.register.form.submit')"
                    type="submit"
                    spinner="register"
                />

                <p class="text-center text-xs">
                    {{ __('auth::ui.register.form.policy_agreement') }}
                </p>
            </div>

            @if (\Illuminate\Support\Facades\Route::has('login'))
                <p class="text-center text-sm">
                    {{ __('auth::ui.register.form.has_account') }}
                    <a class="font-medium underline" href="{{ route('login') }}" wire:navigate>
                        {{ __('auth::ui.register.form.login_now') }}
                    </a>
                </p>
            @endif
        </div>
    </x-ui::form>
</x-ui::card>