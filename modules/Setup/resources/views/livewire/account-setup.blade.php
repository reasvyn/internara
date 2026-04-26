<x-setup::layouts.setup-wizard>
    <x-slot:header>
        <x-setup::wizard-header 
            step="4"
            :title="__('setup::wizard.account.headline')"
            :description="__('setup::wizard.account.description', ['app' => setting('app_name')])"
            badgeIcon="tabler.shield-check"
            :badgeText="__('auth::ui.register_super_admin.sovereign_label')"
        />

        <div 
            class="mt-12 flex flex-wrap items-center gap-4"
            x-data="{ canContinue: @json($this->isRecordExists) }"
            @super_admin_registered.window="canContinue = true"
        >
            <x-ui::button
                variant="secondary"
                :label="__('setup::wizard.common.back')"
                wire:click="backToPrev"
            />
            <x-ui::button
                variant="primary"
                class="btn-lg px-12 shadow-lg shadow-primary/20"
                :label="__('setup::wizard.common.continue')"
                wire:click="nextStep"
                x-bind:disabled="!canContinue"
                spinner
            />
        </div>
    </x-slot>

    <x-slot:content>
        <div class="w-full">
            <x-ui::honeypot wire:model="contact_me" />
            <x-ui::turnstile wire:model="turnstile" class="mb-8" />
            
            <div class="bg-base-100 rounded-3xl p-8 md:p-12 shadow-sm border border-base-content/5">
                @slotRender('register.super-admin')
            </div>
        </div>
    </x-slot>
</x-setup::layouts.setup-wizard>