<div class="relative flex w-full min-h-[60vh] items-center justify-center p-6 overflow-hidden">
    <!-- Background Decor -->
    <div class="absolute -top-24 -left-24 w-96 h-96 bg-primary/10 rounded-full blur-3xl"></div>
    <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-secondary/10 rounded-full blur-3xl"></div>

    <div class="relative w-full max-w-4xl z-10">
        <div class="text-center mb-16">
            <h1 class="text-4xl md:text-6xl font-extrabold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-primary to-secondary">
                {{ __('setup::wizard.welcome.title') }}
            </h1>
            <p class="mt-6 text-xl text-base-content/60 font-medium max-w-2xl mx-auto">
                {{ __('setup::wizard.welcome.headline') }}
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="group p-8 rounded-3xl bg-white/50 backdrop-blur-xl border border-white/50 shadow-xl shadow-primary/5 hover:border-primary/30 transition-all duration-500">
                <div class="mb-6 inline-flex p-3 rounded-2xl bg-primary/10 group-hover:bg-primary/20 transition-colors">
                    <x-ui::icon name="tabler.puzzle" class="size-8 text-primary" />
                </div>
                <h3 class="text-lg font-bold text-base-content">{{ __('setup::wizard.welcome.problem.title') }}</h3>
                <p class="text-sm mt-3 text-base-content/70 leading-relaxed">{{ __('setup::wizard.welcome.problem.description') }}</p>
            </div>
            
            <div class="group p-8 rounded-3xl bg-white/50 backdrop-blur-xl border border-white/50 shadow-xl shadow-secondary/5 hover:border-secondary/30 transition-all duration-500">
                <div class="mb-6 inline-flex p-3 rounded-2xl bg-secondary/10 group-hover:bg-secondary/20 transition-colors">
                    <x-ui::icon name="tabler.rocket" class="size-8 text-secondary" />
                </div>
                <h3 class="text-lg font-bold text-base-content">{{ __('setup::wizard.welcome.solution.title') }}</h3>
                <p class="text-sm mt-3 text-base-content/70 leading-relaxed">{{ __('setup::wizard.welcome.solution.description') }}</p>
            </div>
            
            <div class="group p-8 rounded-3xl bg-white/50 backdrop-blur-xl border border-white/50 shadow-xl shadow-accent/5 hover:border-accent/30 transition-all duration-500">
                <div class="mb-6 inline-flex p-3 rounded-2xl bg-accent/10 group-hover:bg-accent/20 transition-colors">
                    <x-ui::icon name="tabler.map-pin" class="size-8 text-accent" />
                </div>
                <h3 class="text-lg font-bold text-base-content">{{ __('setup::wizard.welcome.journey.title') }}</h3>
                <p class="text-sm mt-3 text-base-content/70 leading-relaxed">{{ __('setup::wizard.welcome.journey.description') }}</p>
            </div>
        </div>

        <div class="mt-20 text-center">
            <x-ui::button
                variant="primary"
                class="btn-lg px-12 text-lg shadow-xl shadow-primary/30 hover:scale-105 active:scale-95 transition-all duration-300"
                :label="__('setup::wizard.welcome.cta')"
                wire:click="nextStep"
                icon="tabler.player-play"
            />
        </div>
    </div>
</div>
