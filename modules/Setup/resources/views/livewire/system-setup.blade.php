<x-setup::layouts.setup-wizard>
    <x-slot:header>
        <div class="max-w-4xl">
            <x-ui::badge variant="metadata" class="mb-12">
                {{ __('setup::wizard.steps', ['current' => 7, 'total' => 8]) }}
            </x-ui::badge>

            <h1 class="text-4xl font-extrabold tracking-tight text-base-content md:text-5xl lg:text-6xl leading-[1.1]">
                {{ __('setup::wizard.system.headline') }}
            </h1>

            <div class="mt-8 space-y-6">
                <p class="text-lg text-base-content/70 leading-relaxed max-w-2xl">
                    {{ __('setup::wizard.system.description', ['app' => setting('app_name')]) }}
                </p>
                
                <!-- Unified System Info Badge -->
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary/5 border border-primary/10">
                    <x-ui::icon name="tabler.info-circle" class="size-4 text-primary" />
                    <span class="text-xs font-bold uppercase tracking-widest text-primary">
                        {{ __('setup::wizard.system.description_extra') }} &bull; {{ __('setup::wizard.common.later_at_settings') }}
                    </span>
                </div>
            </div>
        </div>

        <div 
            class="mt-12 flex flex-wrap items-center gap-4" 
            x-data="{ mailHost: @entangle('mail_host').live }"
        >
            <x-ui::button
                variant="secondary"
                :label="__('setup::wizard.common.back')"
                wire:click="backToPrev"
            />
            <x-ui::button
                variant="primary"
                class="btn-lg px-12 shadow-lg shadow-primary/20"
                wire:click="skip"
                spinner="skip"
            >
                <span x-text="mailHost ? '{{ __('setup::wizard.common.continue') }}' : '{{ __('setup::wizard.system.skip') }}'"></span>
            </x-ui::button>
        </div>
    </x-slot>

    <x-slot:content>
        <div class="space-y-12">
            <x-ui::honeypot wire:model="contact_me" />
            <x-ui::turnstile wire:model="turnstile" class="mb-4" />

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
                <!-- SMTP Configuration -->
                <div class="lg:col-span-7 space-y-8">
                    <div class="bg-base-100 rounded-3xl p-8 md:p-12 shadow-sm border border-base-content/5">
                        <div class="mb-10">
                            <h3 class="text-2xl font-bold text-base-content">{{ __('setup::wizard.system.smtp_configuration') }}</h3>
                            <p class="text-sm text-base-content/50 mt-1">{{ __('setup::wizard.system.smtp_configuration_desc') ?? 'Configure your outbound mail delivery service.' }}</p>
                        </div>

                        <div class="grid grid-cols-1 gap-8">
                            <x-ui::input
                                :label="__('setup::wizard.system.fields.smtp_host')"
                                wire:model.live.debounce.500ms="mail_host"
                                placeholder="smtp.example.com"
                                icon="tabler.server"
                            />
                            
                            <div class="grid grid-cols-2 gap-8">
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

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
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

                        <div class="mt-12 flex justify-start">
                            <x-ui::button
                                variant="secondary"
                                class="border-info/30 text-info hover:bg-info/5 hover:border-info px-8"
                                :label="__('setup::wizard.system.test_connection')"
                                icon="tabler.plug-connected"
                                wire:click="testConnection"
                                spinner="testConnection"
                            />
                        </div>
                    </div>
                </div>

                <!-- Sender Information -->
                <div class="lg:col-span-5 space-y-8">
                    <div class="bg-base-100 rounded-3xl p-8 md:p-12 shadow-sm border border-base-content/5">
                        <div class="mb-10">
                            <h3 class="text-2xl font-bold text-base-content">{{ __('setup::wizard.system.sender_information') }}</h3>
                            <p class="text-sm text-base-content/50 mt-1">{{ __('setup::wizard.system.sender_information_desc') ?? 'Define the identity of outgoing system emails.' }}</p>
                        </div>

                        <div class="grid grid-cols-1 gap-8">
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

                        <div class="mt-12 flex justify-end">
                            <x-ui::button
                                variant="primary"
                                class="w-full btn-lg shadow-lg shadow-primary/10"
                                :label="__('setup::wizard.common.save')"
                                icon="tabler.device-floppy"
                                wire:click="save"
                                spinner="save"
                            />
                        </div>
                    </div>

                    <!-- Guidance Note -->
                    <div class="p-6 rounded-2xl bg-primary/5 border border-primary/10">
                        <div class="flex gap-4">
                            <x-ui::icon name="tabler.bulb" class="size-6 text-primary shrink-0" />
                            <div>
                                <h4 class="text-sm font-bold text-primary">Pro Tip</h4>
                                <p class="text-xs text-base-content/60 mt-1 leading-relaxed">
                                    {{ __('setup::wizard.system.guidance_note') ?? 'Use a dedicated SMTP service like Mailgun, Resend, or Amazon SES for reliable delivery of internship notifications.' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>
</x-setup::layouts.setup-wizard>
