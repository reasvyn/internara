<x-setup::layouts.setup-wizard>
    <x-slot:header>
        <x-ui::badge variant="metadata" aos="fade-down" data-aos-delay="200" class="mb-12">
            {{ __('setup::wizard.steps', ['current' => 6, 'total' => 8]) }}
        </x-ui::badge>

        <h1 class="text-4xl font-bold tracking-tight text-base-content" data-aos="fade-right" data-aos-delay="400">
            {{ __('setup::wizard.internship.headline') }}
        </h1>

        <div class="mt-6 space-y-4" data-aos="fade-right" data-aos-delay="600">
            <p class="text-base-content/70 leading-relaxed">
                {{ __('setup::wizard.internship.description', ['app' => setting('app_name')]) }}
            </p>
            <p class="text-xs font-semibold uppercase tracking-widest text-accent">
                {{ __('setup::wizard.common.later_at_settings') }}
            </p>
        </div>

        <div class="mt-10 flex items-center gap-4" data-aos="fade-up" data-aos-delay="800">
            <x-ui::button
                variant="secondary"
                :label="__('setup::wizard.common.back')"
                wire:click="backToPrev"
            />
            <x-ui::button
                variant="primary"
                :label="__('setup::wizard.common.continue')"
                wire:click="nextStep"
                :disabled="$this->disableNextStep"
                spinner
            />
        </div>
    </x-slot>

    <x-slot:content>
        <div data-aos="fade-left" data-aos-delay="400">
            @slotRender('internship-manager')
        </div>
    </x-slot>
</x-setup::layouts.setup-wizard>