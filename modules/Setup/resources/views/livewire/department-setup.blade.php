<x-setup::layouts.setup-wizard :step="4" :totalSteps="6">
    <x-slot:header>
        <x-setup::wizard-header 
            step="4"
            :title="__('setup::wizard.department.title')"
            :description="__('setup::wizard.department.description', ['app' => setting('app_name', 'Internara')])"
            badgeText="Pathways"
        />
    </x-slot:header>

    <x-slot:content>
        <div class="p-5 sm:p-6">
            @slotRender('department-manager')
        </div>
    </x-slot:content>

    <x-slot:footer>
        <x-setup::action-footer 
            :canContinue="$this->canContinue"
        />
    </x-slot:footer>
</x-setup::layouts.setup-wizard>
