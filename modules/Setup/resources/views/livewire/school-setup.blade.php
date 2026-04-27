<x-setup::layouts.setup-wizard :step="2" :totalSteps="7">
    <x-slot:header>
        <x-setup::wizard-header 
            step="2"
            :title="__('setup::wizard.school.title')"
            :description="__('setup::wizard.school.description', ['app' => setting('app_name', 'Internara')])"
            badgeText="School"
        />
    </x-slot:header>

    <x-slot:content>
        <div class="p-5 sm:p-6">
            @slotRender('school-form')
        </div>
    </x-slot:content>

    <x-slot:footer>
        <x-setup::action-footer 
            :isRecordExists="$this->isRecordExists"
            :continueLabel="__('setup::wizard.common.save_continue')"
        />
    </x-slot:footer>
</x-setup::layouts.setup-wizard>
