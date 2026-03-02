<x-setup::layouts.setup-wizard>
    <x-slot:header>
        <div>
            <x-ui::badge variant="metadata" class="mb-12">
                {{ __('setup::wizard.steps', ['current' => 3, 'total' => 8]) }}
            </x-ui::badge>

            <h1 class="text-4xl font-bold tracking-tight text-base-content">
                {{ __('setup::wizard.school.headline') }}
            </h1>

            <div class="mt-6 space-y-4">
                <p class="text-base-content/70 leading-relaxed">
                    {{ __('setup::wizard.school.description', ['app' => setting('app_name')]) }}
                </p>
                <p class="text-xs font-semibold uppercase tracking-widest text-accent">
                    {{ __('setup::wizard.common.later_at_settings') }}
                </p>
            </div>
        </div>

        <div 
            class="mt-10 flex items-center gap-4"
            x-data="{ canContinue: @json($this->isRecordExists) }"
            @school_saved.window="canContinue = true"
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
        <div class="space-y-6">
            <x-ui::turnstile wire:model="turnstile" class="mb-4" />
            @slotRender('school-manager')
        </div>
    </x-slot>
</x-setup::layouts.setup-wizard>