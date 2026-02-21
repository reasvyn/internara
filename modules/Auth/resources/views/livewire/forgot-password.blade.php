<div class="flex w-full items-center justify-center">
    <x-ui::card
        wire:key="forgot-password-card"
        class="w-full max-w-lg text-center"
        :title="__('auth::ui.forgot_password.title')"
        :subtitle="__('auth::ui.forgot_password.subtitle')"
    >
        <div class="flex flex-col gap-8">
            <x-ui::form class="text-start" wire:submit="sendResetLink">
                <div wire:key="forgot-email">
                    <x-ui::input
                        type="email"
                        icon="tabler.mail"
                        :label="__('auth::ui.forgot_password.form.email')"
                        :placeholder="__('auth::ui.forgot_password.form.email_placeholder')"
                        wire:model="email"
                        required
                        autofocus
                    />
                </div>

                <div class="mt-4 flex w-full flex-col gap-8" wire:key="forgot-actions">
                    <x-ui::button
                        variant="primary"
                        class="w-full"
                        :label="__('auth::ui.forgot_password.form.submit')"
                        type="submit"
                        spinner
                    />

                    <p class="text-center text-sm">
                        <a
                            class="font-medium underline"
                            href="{{ route('login') }}"
                            wire:navigate
                        >
                            {{ __('auth::ui.forgot_password.form.back_to_login') }}
                        </a>
                    </p>
                </div>
            </x-ui::form>
        </div>
    </x-ui::card>
</div>
