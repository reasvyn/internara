<x-setup::layouts.setup-wizard>
    <x-slot:header>
        <x-ui::badge variant="metadata" aos="fade-down" data-aos-delay="200" class="mb-12">
            {{ __('setup::wizard.steps', ['current' => 7, 'total' => 8]) }}
        </x-ui::badge>

        <h1 class="text-4xl font-bold tracking-tight text-base-content" data-aos="fade-right" data-aos-delay="400">
            {{ __('setup::wizard.system.headline') }}
        </h1>

        <div class="mt-6 space-y-4" data-aos="fade-right" data-aos-delay="600">
            <p class="text-base-content/70 leading-relaxed">
                {{ __('setup::wizard.system.description', ['app' => setting('app_name')]) }}
            </p>
            <p class="text-xs font-semibold uppercase tracking-widest text-accent">
                {{ __('setup::wizard.system.description_extra') }}
            </p>
        </div>

        <div class="mt-10 flex flex-wrap items-center gap-4" data-aos="fade-up" data-aos-delay="800">
            <x-ui::button
                variant="secondary"
                :label="__('setup::wizard.buttons.back')"
                wire:click="backToPrev"
            />
            <x-ui::button
                variant="tertiary"
                :label="__('setup::wizard.system.skip')"
                wire:click="skip"
            />
            <x-ui::button
                variant="secondary"
                class="border-info/50 text-info hover:bg-info/5 hover:border-info"
                :label="__('setup::wizard.system.test_connection')"
                wire:click="testConnection"
                spinner="testConnection"
            />
            <x-ui::button
                variant="primary"
                :label="__('setup::wizard.buttons.save_continue')"
                wire:click="save"
                spinner="save"
            />
        </div>
    </x-slot>

    <x-slot:content>
        <div class="space-y-6" data-aos="fade-left" data-aos-delay="400">
            <x-ui::card title="{{ __('SMTP Configuration') }}" separator>
                <div class="grid grid-cols-1 gap-6">
                    <x-ui::input
                        :label="__('setup::wizard.system.fields.smtp_host')"
                        wire:model="mail_host"
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
        </div>
    </x-slot>
</x-setup::layouts.setup-wizard>