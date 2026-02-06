<x-setup::layouts.setup-wizard>
    <x-slot:header>
        <x-ui::badge priority="metadata" aos="fade-down" data-aos-delay="200" class="mb-12">
            {{ __('setup::wizard.steps', ['current' => 4, 'total' => 8]) }}
        </x-ui::badge>

        <h1 class="text-4xl font-bold tracking-tight text-base-content" data-aos="fade-right" data-aos-delay="400">
            {{ __('setup::wizard.account.headline') }}
        </h1>

        <p class="mt-6 text-base-content/70 leading-relaxed" data-aos="fade-right" data-aos-delay="600">
            {{ __('setup::wizard.account.description', ['app' => setting('app_name')]) }}
        </p>

        <div class="mt-10 flex items-center gap-4" data-aos="fade-up" data-aos-delay="800">
            <x-ui::button
                priority="secondary"
                :label="__('setup::wizard.common.back')"
                wire:click="backToPrev"
            />
            <x-ui::button
                priority="primary"
                :label="__('setup::wizard.common.continue')"
                wire:click="nextStep"
                :disabled="$this->disableNextStep"
                spinner
            />
        </div>
    </x-slot>

    <x-slot:content>
        <div data-aos="fade-left" data-aos-delay="400">
            @slotRender('register.super-admin')
        </div>
    </x-slot>
</x-setup::layouts.setup-wizard>