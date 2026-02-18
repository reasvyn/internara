<x-setup::layouts.setup-wizard>
    <x-slot:header>
        <div>
            <x-ui::badge variant="metadata" class="mb-12">
                {{ __('setup::wizard.steps', ['current' => 7, 'total' => 8]) }}
            </x-ui::badge>

            <h1 class="text-4xl font-bold tracking-tight text-base-content">
                {{ __('setup::wizard.system.headline') }}
            </h1>

            <div class="mt-6 space-y-4">
                <p class="text-base-content/70 leading-relaxed">
                    {{ __('setup::wizard.system.description', ['app' => setting('app_name')]) }}
                </p>
                <p class="text-xs font-semibold uppercase tracking-widest text-accent">
                    {{ __('setup::wizard.system.description_extra') }}
                </p>
            </div>
        </div>

        <div 
            class="mt-10 flex flex-wrap items-center gap-4" 
            x-data="{ mailHost: @entangle('mail_host').live }"
        >
            <x-ui::button
                variant="secondary"
                :label="__('setup::wizard.common.back')"
                wire:click="backToPrev"
            />
            <x-ui::button
                variant="primary"
                wire:click="skip"
                spinner="skip"
            >
                <span x-text="mailHost ? '{{ __('setup::wizard.common.continue') }}' : '{{ __('setup::wizard.system.skip') }}'"></span>
            </x-ui::button>
        </div>
    </x-slot>

    <x-slot:content>
        <div class="space-y-6">
            <x-ui::card title="{{ __('SMTP Configuration') }}" separator>
                <div class="grid grid-cols-1 gap-6">
                    <x-ui::input
                        :label="__('setup::wizard.system.fields.smtp_host')"
                        wire:model.live.debounce.500ms="mail_host"
                        placeholder="smtp.example.com"
                        icon="tabler.server"
                    />
                    <div class="grid grid-cols-2 gap-6">
                        <x-ui::input
                            :label="__('setup::wizard.system.fields.smtp_port')"
                            wire:model="mail_port"
                            placeholder="587"
                            icon="tabler.hash"
                        />
                        <x-ui::input
                            :label="__('setup::wizard.system.fields.encryption')"
                            wire:model="mail_encryption"
                            placeholder="tls"
                            icon="tabler.lock"
                        />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-ui::input
                            :label="__('setup::wizard.system.fields.username')"
                            wire:model="mail_username"
                            icon="tabler.user"
                        />
                        <x-ui::input
                            :label="__('setup::wizard.system.fields.password')"
                            wire:model="mail_password"
                            type="password"
                            icon="tabler.key"
                        />
                    </div>
                </div>
            </x-ui::card>

            <x-ui::card title="{{ __('Sender Information') }}" separator>
                <div class="grid grid-cols-1 gap-6">
                    <x-ui::input
                        :label="__('setup::wizard.system.fields.from_email')"
                        wire:model="mail_from_address"
                        placeholder="no-reply@school.id"
                        icon="tabler.mail"
                    />
                    <x-ui::input
                        :label="__('setup::wizard.system.fields.from_name')"
                        wire:model="mail_from_name"
                        icon="tabler.id"
                    />
                </div>
            </x-ui::card>

            <div class="flex items-center justify-end gap-4 mt-4">
                <x-ui::button
                    variant="secondary"
                    class="border-info/50 text-info hover:bg-info/5 hover:border-info"
                    :label="__('setup::wizard.system.test_connection')"
                    wire:click="testConnection"
                    spinner="testConnection"
                />
                <x-ui::button
                    variant="primary"
                    :label="__('setup::wizard.common.save')"
                    wire:click="save"
                    spinner="save"
                />
            </div>
        </div>
    </x-slot>
</x-setup::layouts.setup-wizard>