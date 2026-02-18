<x-setup::layouts.setup-wizard>
    <x-slot:header>
        <div>
            <x-ui::badge variant="metadata" class="mb-12">
                {{ __('setup::wizard.steps', ['current' => 4, 'total' => 8]) }}
            </x-ui::badge>

            <h1 class="text-4xl font-bold tracking-tight text-base-content">
                {{ __('setup::wizard.account.headline') }}
            </h1>

            <p class="mt-6 text-base-content/70 leading-relaxed">
                {{ __('setup::wizard.account.description', ['app' => setting('app_name')]) }}
            </p>
        </div>

        <div 
            class="mt-10 flex items-center gap-4"
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
                :label="__('setup::wizard.common.continue')"
                wire:click="nextStep"
                x-bind:disabled="!canContinue"
                spinner
            />
        </div>
    </x-slot>

    <x-slot:content>
        <div>
            @slotRender('register.super-admin')
        </div>
    </x-slot>
</x-setup::layouts.setup-wizard>