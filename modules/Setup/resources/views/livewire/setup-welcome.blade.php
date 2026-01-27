<div class="container mx-auto flex flex-col items-center justify-center gap-12 text-center">
    <!-- Main Headline -->
    <div class="max-w-prose">
        <h1 class="text-3xl font-bold">{{ __('setup::wizard.welcome.headline') }}</h1>
    </div>

    <!-- 3-Column Feature Grid -->
    <div class="grid grid-cols-1 gap-10 text-center md:grid-cols-3 md:gap-8">
        <!-- Column 1: The Problem -->
        <div class="flex flex-col items-center">
            <div class="mb-4 text-4xl">🧩</div>
            <h3 class="text-lg font-semibold">{{ __('setup::wizard.welcome.problem.title') }}</h3>
            <p class="mt-2 text-sm text-base-content/70">
                {{ __('setup::wizard.welcome.problem.description') }}
            </p>
        </div>

        <!-- Column 2: The Solution -->
        <div class="flex flex-col items-center">
            <div class="mb-4 text-4xl">🎓</div>
            <h3 class="text-lg font-semibold">{{ __('setup::wizard.welcome.solution.title') }}</h3>
            <p class="mt-2 text-sm text-base-content/70">
                {{ __('setup::wizard.welcome.solution.description') }}
            </p>
        </div>

        <!-- Column 3: The Journey -->
        <div class="flex flex-col items-center">
            <div class="mb-4 text-4xl">🚀</div>
            <h3 class="text-lg font-semibold">{{ __('setup::wizard.welcome.journey.title') }}</h3>
            <p class="mt-2 text-sm text-base-content/70">
                {{ __('setup::wizard.welcome.journey.description') }}
            </p>
        </div>
    </div>

    <!-- Call to Action Button -->
    <div>
        <x-ui::button
            class="btn-primary"
            :label="__('setup::wizard.welcome.cta')"
            wire:click="nextStep"
        />
    </div>
</div>