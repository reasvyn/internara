<div class="flex size-full flex-1 flex-col items-center justify-center">
    <x-ui::card
        wire:key="register-super-admin-card"
        class="border-base-300 w-full max-w-lg rounded-xl border text-center shadow-lg"
        :title="__('auth::ui.register_super_admin.title')"
        :subtitle="__('auth::ui.register_super_admin.subtitle')"
    >
        <x-ui::form class="text-start" wire:submit="register">
            <div wire:key="rsa-name">
                <x-ui::input
                    wire:model="form.name"
                    type="text"
                    :label="__('auth::ui.register_super_admin.form.name')"
                    :placeholder="__('auth::ui.register_super_admin.form.name_placeholder')"
                    required
                    disabled
                />
            </div>
            <div wire:key="rsa-email">
                <x-ui::input
                    wire:model="form.email"
                    type="email"
                    :label="__('auth::ui.register_super_admin.form.email')"
                    :placeholder="__('auth::ui.register_super_admin.form.email_placeholder')"
                    required
                    autofocus
                />
            </div>
            <div wire:key="rsa-password">
                <x-ui::input
                    wire:model="form.password"
                    type="password"
                    :label="__('auth::ui.register_super_admin.form.password')"
                    :placeholder="__('auth::ui.register_super_admin.form.password_placeholder')"
                    :hint="__('auth::ui.register_super_admin.form.password_hint')"
                    required
                />
            </div>
            <div wire:key="rsa-password-conf">
                <x-ui::input
                    wire:model="form.password_confirmation"
                    type="password"
                    :label="__('auth::ui.register_super_admin.form.password_confirmation')"
                    :placeholder="__('auth::ui.register_super_admin.form.password_confirmation_placeholder')"
                    required
                />
            </div>

            <div class="mt-4 flex flex-col gap-8" wire:key="rsa-actions">
                <div class="w-full space-y-2">
                    <x-ui::button
                        class="btn-primary w-full"
                        :label="__('auth::ui.register_super_admin.form.submit')"
                        type="submit"
                        spinner
                    />

                    <p class="text-center text-xs">
                        {{ __('auth::ui.register_super_admin.form.footer_warning') }}
                    </p>
                </div>
            </div>
        </x-ui::form>
    </x-ui::card>
</div>