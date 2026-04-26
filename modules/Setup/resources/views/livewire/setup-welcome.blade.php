<div class="flex-1 flex flex-col items-center justify-center w-full max-w-5xl mx-auto px-6 py-8 md:py-16 lg:py-24">
    <div class="flex flex-col items-center justify-center gap-8 md:gap-12 text-center w-full">
        <!-- Progress Indicator -->
        <x-ui::badge 
            variant="metadata" 
            :value="__('setup::wizard.steps', ['current' => 1, 'total' => 8])" 
            class="animate-fade-in"
        />

        <!-- Main Headline -->
        <div class="max-w-3xl space-y-6">
            <h1 class="text-4xl font-extrabold tracking-tight text-base-content md:text-5xl lg:text-6xl leading-[1.1]">
                {{ __('setup::wizard.welcome.headline') }}
            </h1>
            <p class="text-lg text-base-content/70 max-w-2xl mx-auto leading-relaxed">
                {{ __('setup::wizard.welcome.journey.description') }}
            </p>
        </div>

        <!-- 3-Column Feature Grid -->
        <div class="grid grid-cols-1 gap-8 md:grid-cols-3 md:gap-6 lg:gap-10 w-full mt-4 md:mt-8">
            <!-- Column 1: The Problem -->
            <div class="flex flex-col items-center p-6 rounded-2xl bg-base-200/30 border border-transparent transition-all hover:bg-base-200/50">
                <div class="mb-5 text-4xl md:text-5xl filter grayscale opacity-80" role="img" aria-label="puzzle">🧩</div>
                <h3 class="text-lg font-bold text-base-content">{{ __('setup::wizard.welcome.problem.title') }}</h3>
                <p class="mt-3 text-sm leading-relaxed text-base-content/60">
                    {{ __('setup::wizard.welcome.problem.description') }}
                </p>
            </div>

            <!-- Column 2: The Solution (Highlighted) -->
            <div class="flex flex-col items-center p-6 rounded-2xl bg-primary/5 border border-primary/10 shadow-sm ring-1 ring-primary/5 transition-all hover:bg-primary/10">
                <div class="mb-5 text-4xl md:text-5xl" role="img" aria-label="graduation cap">🎓</div>
                <h3 class="text-lg font-bold text-primary">{{ __('setup::wizard.welcome.solution.title') }}</h3>
                <p class="mt-3 text-sm leading-relaxed text-base-content/70">
                    {{ __('setup::wizard.welcome.solution.description', ['app' => setting('app_name', 'Internara')]) }}
                </p>
            </div>

            <!-- Column 3: The Journey -->
            <div class="flex flex-col items-center p-6 rounded-2xl bg-base-200/30 border border-transparent transition-all hover:bg-base-200/50">
                <div class="mb-5 text-4xl md:text-5xl filter grayscale opacity-80" role="img" aria-label="rocket">🚀</div>
                <h3 class="text-lg font-bold text-base-content">{{ __('setup::wizard.welcome.journey.title') }}</h3>
                <p class="mt-3 text-sm leading-relaxed text-base-content/60">
                    {{ __('setup::wizard.welcome.journey.description_short') }}
                </p>
            </div>
        </div>

        <!-- Call to Action Button -->
        <div class="mt-6 md:mt-10">
            <x-ui::button
                variant="primary"
                class="btn-lg px-10 md:px-16 shadow-lg shadow-primary/20 transition-transform active:scale-95"
                :label="__('setup::wizard.welcome.cta')"
                wire:click="nextStep"
                spinner
            />
        </div>
    </div>
</div>