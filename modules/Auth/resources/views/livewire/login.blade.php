<div class="flex w-full items-center justify-center">
    <x-ui::card
        wire:key="login-card"
        class="w-full max-w-lg text-center"
        :title="__('auth::ui.login.title')"
        :subtitle="__('auth::ui.login.subtitle')"
    >
        <div class="flex flex-col gap-8">
            <x-ui::form class="text-start" wire:submit="login">
                <div wire:key="login-identifier">
                    <x-ui::input
                        type="text"
                        :label="__('auth::ui.login.form.identifier')"
                        :placeholder="__('auth::ui.login.form.identifier_placeholder')"
                        wire:model="identifier"
                        required
                    />
                </div>

                <div class="relative w-full" wire:key="login-password">
                    <x-ui::input
                        type="password"
                        :label="__('auth::ui.login.form.password')"
                        :placeholder="__('auth::ui.login.form.password_placeholder')"
                        wire:model="password"
                        required
                    />

                    @if (\Illuminate\Support\Facades\Route::has('forgot-password'))
                        <a
                            class="absolute right-0 top-2 text-xs font-medium underline"
                            href="{{ route('forgot-password') }}"
                        >
                            {{ __('auth::ui.login.form.forgot_password') }}
                        </a>
                    @endif
                </div>

                <div wire:key="login-remember">
                    <x-ui::checkbox :label="__('auth::ui.login.form.remember_me')" wire:model="remember" />
                </div>

                @if(config('services.cloudflare.turnstile.site_key'))
                    <div wire:key="login-captcha">
                        <x-ui::turnstile fieldName="captcha_token" />
                    </div>
                @endif

                <div class="mt-4 flex w-full flex-col gap-8" wire:key="login-actions">
                    <x-ui::button
                        variant="primary"
                        class="w-full"
                        :label="__('auth::ui.login.form.submit')"
                        type="submit"
                        spinner
                    />

                    @if (\Illuminate\Support\Facades\Route::has('register'))
                        <p class="text-center text-sm">
                            {{ __('auth::ui.login.form.no_account') }}
                            <a
                                class="font-medium underline"
                                href="{{ route('register') }}"
                                wire:navigate
                            >
                                {{ __('auth::ui.login.form.register_now') }}
                            </a>
                        </p>
                    @endif
                </div>
            </x-ui::form>
        </div>
    </x-ui::card>
</div>