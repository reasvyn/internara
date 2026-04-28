<x-setup::layouts.setup-wizard :step="5" :totalSteps="6">
    <x-slot:header>
        <x-setup::wizard-header 
            step="5"
            :title="__('setup::wizard.internship.title')"
            :description="__('setup::wizard.internship.description', ['app' => setting('app_name', 'Internara')])"
            badgeText="Period"
        />
    </x-slot:header>

    <x-slot:content>
        <div class="p-5 sm:p-6">
            @slotRender('internship-manager')
        </div>
    </x-slot:content>

    <x-slot:footer>
        <x-setup::action-footer 
            :canContinue="$this->canContinue"
        />
    </x-slot:footer>
</x-setup::layouts.setup-wizard>
