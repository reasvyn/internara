<x-setup::layouts.setup-wizard>
    <x-slot:header>
        <x-setup::wizard-header 
            step="5"
            :title="__('setup::wizard.department.headline')"
            :description="__('setup::wizard.department.description', ['app' => setting('app_name')])"
            :badgeText="__('setup::wizard.common.later_at_settings')"
        />

        <div 
            class="mt-12 flex flex-wrap items-center gap-4"
            x-data="{ canContinue: @json($this->isRecordExists) }"
            @department:saved.window="canContinue = true"
            @department:deleted.window="canContinue = $event.detail.exists"
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
            <div class="bg-base-100 rounded-3xl p-8 md:p-12 shadow-sm border border-base-content/5">
                @slotRender('department-manager')
            </div>
        </div>
    </x-slot>
</x-setup::layouts.setup-wizard>