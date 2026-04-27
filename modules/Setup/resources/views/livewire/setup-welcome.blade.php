<div class="flex w-full min-h-[60vh] items-center justify-center p-6">
    <div class="w-full max-w-4xl">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-extrabold tracking-tight text-base-content sm:text-5xl">
                {{ __('setup::wizard.welcome.title') }}
            </h1>
            <p class="mt-4 text-xl text-base-content/60">
                {{ __('setup::wizard.welcome.headline') }}
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="p-8 rounded-2xl bg-base-100 border border-base-content/10 shadow-sm hover:shadow-md transition-all duration-300">
                <x-ui::icon name="tabler.puzzle" class="size-10 text-primary mb-6" />
                <h3 class="text-lg font-bold text-base-content">{{ __('setup::wizard.welcome.problem.title') }}</h3>
                <p class="text-sm mt-3 text-base-content/70 leading-relaxed">{{ __('setup::wizard.welcome.problem.description') }}</p>
            </div>
            
            <div class="p-8 rounded-2xl bg-base-100 border border-base-content/10 shadow-sm hover:shadow-md transition-all duration-300">
                <x-ui::icon name="tabler.rocket" class="size-10 text-primary mb-6" />
                <h3 class="text-lg font-bold text-base-content">{{ __('setup::wizard.welcome.solution.title') }}</h3>
                <p class="text-sm mt-3 text-base-content/70 leading-relaxed">{{ __('setup::wizard.welcome.solution.description') }}</p>
            </div>
            
            <div class="p-8 rounded-2xl bg-base-100 border border-base-content/10 shadow-sm hover:shadow-md transition-all duration-300">
                <x-ui::icon name="tabler.map-pin" class="size-10 text-primary mb-6" />
                <h3 class="text-lg font-bold text-base-content">{{ __('setup::wizard.welcome.journey.title') }}</h3>
                <p class="text-sm mt-3 text-base-content/70 leading-relaxed">{{ __('setup::wizard.welcome.journey.description') }}</p>
            </div>
        </div>

        <div class="mt-16 text-center">
            <x-ui::button
                variant="primary"
                class="btn-lg px-12 text-lg shadow-lg shadow-primary/20 hover:shadow-xl hover:shadow-primary/30 transition-all duration-300"
                :label="__('setup::wizard.welcome.cta')"
                wire:click="nextStep"
                icon="tabler.player-play"
            />
        </div>
    </div>
</div>
