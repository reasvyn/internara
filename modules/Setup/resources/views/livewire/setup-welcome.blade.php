<div class="container mx-auto flex flex-col items-center justify-center gap-12 text-center py-12">
    <x-ui::badge 
        variant="metadata" 
        :value="__('setup::wizard.steps', ['current' => 1, 'total' => 8])" 
    />

    <!-- Main Headline -->
    <div class="max-w-2xl">
        <h1 class="text-4xl font-bold tracking-tight text-base-content md:text-5xl">
            {{ __('setup::wizard.welcome.headline') }}
        </h1>
    </div>

    <!-- 3-Column Feature Grid -->
    <div class="grid grid-cols-1 gap-10 text-center md:grid-cols-3 md:gap-8 lg:gap-12">
        <!-- Column 1: The Problem -->
        <div class="flex flex-col items-center">
            <div class="mb-6 text-5xl" role="img" aria-label="puzzle">🧩</div>
            <h3 class="text-xl font-bold text-base-content">{{ __('setup::wizard.welcome.problem.title') }}</h3>
            <p class="mt-3 text-sm leading-relaxed text-base-content/60">
                {{ __('setup::wizard.welcome.problem.description') }}
            </p>
        </div>

        <!-- Column 2: The Solution -->
        <div class="flex flex-col items-center">
            <div class="mb-6 text-5xl" role="img" aria-label="graduation cap">🎓</div>
            <h3 class="text-xl font-bold text-base-content">{{ __('setup::wizard.welcome.solution.title') }}</h3>
            <p class="mt-3 text-sm leading-relaxed text-base-content/60">
                {{ __('setup::wizard.welcome.solution.description', ['app' => setting('app_name', 'Internara')]) }}
            </p>
        </div>

        <!-- Column 3: The Journey -->
        <div class="flex flex-col items-center">
            <div class="mb-6 text-5xl" role="img" aria-label="rocket">🚀</div>
            <h3 class="text-xl font-bold text-base-content">{{ __('setup::wizard.welcome.journey.title') }}</h3>
            <p class="mt-3 text-sm leading-relaxed text-base-content/60">
                {{ __('setup::wizard.welcome.journey.description') }}
            </p>
        </div>
    </div>

    <!-- Call to Action Button -->
    <div>
        <x-ui::button
            variant="primary"
            class="btn-lg px-12"
            :label="__('setup::wizard.welcome.cta')"
            wire:click="nextStep"
            spinner
        />
    </div>
</div>