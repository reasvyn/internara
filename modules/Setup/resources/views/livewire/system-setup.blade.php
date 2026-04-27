<x-setup::layouts.setup-wizard :step="6" :totalSteps="7">
    <x-slot:header>
        <x-setup::wizard-header 
            step="6"
            :title="__('setup::wizard.system.title')"
            :description="__('setup::wizard.system.description', ['app' => setting('app_name', 'Internara')])"
            badgeText="Optional"
        />
    </x-slot:header>

    <x-slot:content>
        <div class="space-y-6" x-data="{ mailHost: @entangle('mail_host').live }">
            <!-- SMTP Form -->
            <div class="space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-ui::input
                        :label="__('setup::wizard.system.fields.smtp_host')"
                        wire:model.live.debounce.500ms="mail_host"
                        placeholder="smtp.example.com"
                        icon="tabler.server"
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
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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

            <!-- Sender Info -->
            <div class="pt-4 border-t border-base-200/50 dark:border-base-200/30">
                <h3 class="text-sm font-semibold text-base-content/70 dark:text-base-content/60 mb-4">
                    {{ __('setup::wizard.system.sender_information') }}
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
            </div>

            <!-- Test Connection -->
            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <x-ui::button
                    variant="secondary"
                    class="btn-md"
                    :label="__('setup::wizard.system.test_connection')"
                    icon="tabler.plug-connected"
                    wire:click="testConnection"
                    spinner="testConnection"
                />
                <x-ui::button
                    variant="ghost"
                    class="btn-md"
                    :label="__('setup::wizard.system.skip')"
                    wire:click="skip"
                    spinner="skip"
                />
            </div>
        </div>
    </x-slot:content>

    <x-slot:footer>
        <div class="flex items-center justify-between gap-3">
            <x-ui::button
                variant="tertiary"
                class="btn-md"
                :label="__('setup::wizard.common.back')"
                wire:click="backToPrev"
            />
            <div class="flex items-center gap-3">
                <x-ui::button
                    variant="tertiary"
                    class="btn-md"
                    :label="__('setup::wizard.system.skip')"
                    wire:click="skip"
                />
                <x-ui::button
                    variant="primary"
                    class="btn-md"
                    :label="__('setup::wizard.common.continue')"
                    wire:click="nextStep"
                    spinner
                />
            </div>
        </div>
    </x-slot:footer>
</x-setup::layouts.setup-wizard>
