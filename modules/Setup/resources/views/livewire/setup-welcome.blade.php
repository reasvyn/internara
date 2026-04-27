<div class="flex w-full items-center justify-center">
    <x-ui::card
        wire:key="welcome-card"
        class="w-full max-w-2xl text-center"
        :title="__('setup::wizard.welcome.title')"
    >
        <div class="prose max-w-none text-base-content/70">
            <h2 class="text-2xl font-bold text-base-content">{{ __('setup::wizard.welcome.headline') }}</h2>
            
            <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6 text-left">
                <div class="p-4 rounded-xl bg-base-100 border border-base-content/10">
                    <h3 class="font-bold text-base-content">{{ __('setup::wizard.welcome.problem.title') }}</h3>
                    <p class="text-sm mt-2">{{ __('setup::wizard.welcome.problem.description') }}</p>
                </div>
                <div class="p-4 rounded-xl bg-base-100 border border-base-content/10">
                    <h3 class="font-bold text-base-content">{{ __('setup::wizard.welcome.solution.title') }}</h3>
                    <p class="text-sm mt-2">{{ __('setup::wizard.welcome.solution.description') }}</p>
                </div>
            </div>

            <div class="mt-8 p-6 rounded-xl bg-primary/5 border border-primary/20 text-center">
                <h3 class="font-bold text-primary">{{ __('setup::wizard.welcome.journey.title') }}</h3>
                <p class="text-sm mt-2 text-base-content/80">{{ __('setup::wizard.welcome.journey.description') }}</p>
            </div>
        </div>

        <x-slot:footer class="flex justify-end pt-6">
            <x-ui::button
                variant="primary"
                class="px-8"
                :label="__('setup::wizard.welcome.cta')"
                wire:click="nextStep"
                icon="tabler.arrow-right"
            />
        </x-slot:footer>
    </x-ui::card>
</div>
