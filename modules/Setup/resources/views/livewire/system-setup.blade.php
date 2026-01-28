<x-setup::layouts.setup-wizard>
    <x-slot:header>
        <p class="mb-16 font-bold text-gray-500">
            {{ __('setup::wizard.steps', ['current' => 7, 'total' => 8]) }}
        </p>

        <h1 class="text-3xl font-bold">{{ __('setup::wizard.system.headline') }}</h1>

        <p class="mt-4">
            {{ __('setup::wizard.system.description') }}
        </p>
        <p class="mt-2 text-sm text-base-content/70">
            {{ __('setup::wizard.system.description_extra') }}
        </p>

        <div class="mt-8 flex flex-wrap items-center gap-4">
            <x-ui::button
                class="btn-secondary btn-outline"
                :label="__('setup::wizard.buttons.back')"
                wire:click="backToPrev"
            />
            <x-ui::button
                class="btn-ghost"
                :label="__('setup::wizard.system.skip')"
                wire:click="skip"
            />
            <x-ui::button
                class="btn-info btn-outline"
                :label="__('setup::wizard.system.test_connection')"
                wire:click="testConnection"
                spinner="testConnection"
            />
            <x-ui::button
                class="btn-primary"
                :label="__('setup::wizard.buttons.save_continue')"
                wire:click="save"
                spinner="save"
            />
        </div>
    </x-slot>

    <x-slot:content>
        <div class="space-y-4">
            <x-ui::input
                :label="__('setup::wizard.system.fields.smtp_host')"
                wire:model="mail_host"
                placeholder="smtp.example.com"
            />
            <div class="grid grid-cols-2 gap-4">
                <x-ui::input
                    :label="__('setup::wizard.system.fields.smtp_port')"
                    wire:model="mail_port"
                    placeholder="587"
                />
                <x-ui::input
                    :label="__('setup::wizard.system.fields.encryption')"
                    wire:model="mail_encryption"
                    placeholder="tls"
                />
            </div>
            <x-ui::input
                :label="__('setup::wizard.system.fields.username')"
                wire:model="mail_username"
            />
            <x-ui::input
                :label="__('setup::wizard.system.fields.password')"
                wire:model="mail_password"
                type="password"
            />
            <hr class="my-4 border-base-200" />
            <x-ui::input
                :label="__('setup::wizard.system.fields.from_email')"
                wire:model="mail_from_address"
                placeholder="no-reply@school.id"
            />
            <x-ui::input
                :label="__('setup::wizard.system.fields.from_name')"
                wire:model="mail_from_name"
            />
        </div>
    </x-slot>
</x-setup::layouts.setup-wizard>