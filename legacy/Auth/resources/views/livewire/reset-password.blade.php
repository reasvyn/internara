<div class="flex w-full items-center justify-center">
    <x-ui::card
        wire:key="reset-password-card"
        class="w-full max-w-lg text-center"
        :title="__('auth::ui.reset_password.title')"
        :subtitle="__('auth::ui.reset_password.subtitle')"
    >
        <div class="flex flex-col gap-8">
            <x-ui::form class="text-start" wire:submit="resetPassword">
                <div wire:key="reset-email">
                    <x-ui::input
                        wire:model="email"
                        icon="tabler.mail"
                        :label="__('auth::ui.reset_password.form.email')"
                        displayed
                    />
                </div>

                <div wire:key="reset-password">
                    <x-ui::input
                        type="password"
                        icon="tabler.lock"
                        :label="__('auth::ui.reset_password.form.password')"
                        :placeholder="__('auth::ui.reset_password.form.password_placeholder')"
                        wire:model="password"
                        required
                        autofocus
                    />
                </div>

                <div wire:key="reset-password-conf">
                    <x-ui::input
                        type="password"
                        icon="tabler.lock-check"
                        :label="__('auth::ui.reset_password.form.password_confirmation')"
                        :placeholder="__('auth::ui.reset_password.form.password_confirmation_placeholder')"
                        wire:model="password_confirmation"
                        required
                    />
                </div>

                <div class="mt-4 flex w-full flex-col gap-8" wire:key="reset-actions">
                    <x-ui::button
                        variant="primary"
                        class="w-full"
                        :label="__('auth::ui.reset_password.form.submit')"
                        type="submit"
                        spinner
                    />
                </div>
            </x-ui::form>
        </div>
    </x-ui::card>
</div>
