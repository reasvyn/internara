<x-setup::layouts.setup-wizard>
    <x-slot:header>
        <p class="mb-16 font-bold text-gray-500">
            {{ __('setup::wizard.steps', ['current' => 6, 'total' => 8]) }}
        </p>

        <h1 class="text-3xl font-bold">{{ __('setup::wizard.internship.headline') }}</h1>

        <p class="mt-4">
            {{ __('setup::wizard.internship.description') }}
        </p>
        <p class="mt-2 text-sm text-base-content/70">
            {{ __('setup::wizard.common.later_at_settings') }}
        </p>

        <div class="mt-8 flex items-center gap-4">
            <x-ui::button
                class="btn-secondary btn-outline"
                :label="__('setup::wizard.common.back')"
                wire:click="backToPrev"
            />
            <x-ui::button
                class="btn-primary"
                :label="__('setup::wizard.common.continue')"
                wire:click="nextStep"
                :disabled="$this->disableNextStep"
            />
        </div>
    </x-slot>

    <x-slot:content>
        @slotRender('internship-manager')
    </x-slot>
</x-setup::layouts.setup-wizard>