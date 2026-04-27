<x-setup::layouts.setup-wizard :step="3" :totalSteps="7">
    <x-slot:header>
        <x-setup::wizard-header 
            step="3"
            :title="__('setup::wizard.account.title')"
            :description="__('setup::wizard.account.description', ['app' => setting('app_name', 'Internara')])"
            badgeIcon="tabler.shield-check"
            :badgeText="__('setup::wizard.common.admin_account')"
        />
    </x-slot:header>

    <x-slot:content>
        <div class="p-5 sm:p-6">
            @slotRender('register.super-admin')
        </div>
    </x-slot:content>

    <x-slot:footer>
        <x-setup::action-footer 
            :isRecordExists="$this->isRecordExists"
        />
    </x-slot:footer>
</x-setup::layouts.setup-wizard>
