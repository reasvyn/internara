<x-setup::layouts.setup-wizard :step="1" :totalSteps="7">
    <x-slot:header>
        <x-setup::wizard-header 
            step="1"
            :title="__('setup::wizard.welcome.title')"
            :description="__('setup::wizard.welcome.headline')"
            badgeText="Start"
        />
    </x-slot:header>

    <x-slot:content>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-5 rounded-xl bg-base-200/30 dark:bg-base-200/20 border border-base-200/50 dark:border-base-200/30 hover:border-base-300/50 dark:hover:border-base-300/30 transition-colors">
                <div class="p-2 rounded-lg bg-base-content/5 dark:bg-base-content/10 w-fit mb-3">
                    <x-ui::icon name="tabler.puzzle" class="size-5 text-base-content/60 dark:text-base-content/50" />
                </div>
                <h3 class="text-sm font-semibold text-base-content dark:text-base-content/90 mb-1.5">
                    {{ __('setup::wizard.welcome.problem.title') }}
                </h3>
                <p class="text-xs text-base-content/50 dark:text-base-content/40 leading-relaxed">
                    {{ __('setup::wizard.welcome.problem.description') }}
                </p>
            </div>
            
            <div class="p-5 rounded-xl bg-base-200/30 dark:bg-base-200/20 border border-base-200/50 dark:border-base-200/30 hover:border-base-300/50 dark:hover:border-base-300/30 transition-colors">
                <div class="p-2 rounded-lg bg-base-content/5 dark:bg-base-content/10 w-fit mb-3">
                    <x-ui::icon name="tabler.rocket" class="size-5 text-base-content/60 dark:text-base-content/50" />
                </div>
                <h3 class="text-sm font-semibold text-base-content dark:text-base-content/90 mb-1.5">
                    {{ __('setup::wizard.welcome.solution.title') }}
                </h3>
                <p class="text-xs text-base-content/50 dark:text-base-content/40 leading-relaxed">
                    {{ __('setup::wizard.welcome.solution.description', ['app' => setting('app_name', 'Internara')]) }}
                </p>
            </div>
            
            <div class="p-5 rounded-xl bg-base-200/30 dark:bg-base-200/20 border border-base-200/50 dark:border-base-200/30 hover:border-base-300/50 dark:hover:border-base-300/30 transition-colors">
                <div class="p-2 rounded-lg bg-base-content/5 dark:bg-base-content/10 w-fit mb-3">
                    <x-ui::icon name="tabler.map-pin" class="size-5 text-base-content/60 dark:text-base-content/50" />
                </div>
                <h3 class="text-sm font-semibold text-base-content dark:text-base-content/90 mb-1.5">
                    {{ __('setup::wizard.welcome.journey.title') }}
                </h3>
                <p class="text-xs text-base-content/50 dark:text-base-content/40 leading-relaxed">
                    {{ __('setup::wizard.welcome.journey.description') }}
                </p>
            </div>
        </div>
    </x-slot:content>

    <x-slot:footer>
        <div class="flex items-center justify-end gap-3">
            <x-ui::button
                variant="primary"
                class="btn-md"
                :label="__('setup::wizard.welcome.cta')"
                wire:click="nextStep"
                spinner
                icon="tabler.arrow-right"
                iconPosition="right"
            />
        </div>
    </x-slot:footer>
</x-setup::layouts.setup-wizard>
