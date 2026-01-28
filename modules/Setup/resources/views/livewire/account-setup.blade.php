<x-setup::layouts.setup-wizard>
    <x-slot:header>
        <p class="mb-16 font-bold text-gray-500">
            {{ __('setup::wizard.steps', ['current' => 4, 'total' => 8]) }}
        </p>

        <h1 class="text-3xl font-bold">{{ __('setup::wizard.account.headline') }}</h1>

        <p class="mt-4">
            {{ __('setup::wizard.account.description') }}
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
        @slotRender('register.super-admin')
    </x-slot>
</x-setup::layouts.setup-wizard>