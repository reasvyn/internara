<div class="container mx-auto flex flex-col items-center justify-center gap-8 text-center" data-aos="fade-up">
    <div class="max-w-2xl text-center">
        <x-ui::badge priority="metadata" aos="fade-down" data-aos-delay="200" class="mb-12">
            {{ __('setup::wizard.steps', ['current' => 8, 'total' => 8]) }}
        </x-ui::badge>

        <p class="mb-6 font-bold text-success text-lg" data-aos="zoom-in" data-aos-delay="400">
            {{ __('setup::wizard.complete.badge') }}
        </p>

        <h1 class="text-4xl font-bold tracking-tight text-base-content md:text-5xl" data-aos="fade-up" data-aos-delay="600">
            {{ __('setup::wizard.complete.headline', ['app' => setting('app_name')]) }}
        </h1>

        <div class="mt-8 space-y-4" data-aos="fade-up" data-aos-delay="800">
            <p class="text-base-content/70 leading-relaxed text-lg">
                {{ __('setup::wizard.complete.description', ['app' => setting('app_name')]) }}
            </p>
            <p class="text-base-content/60 leading-relaxed">
                {{ __('setup::wizard.complete.description_extra') }}
            </p>
        </div>

        <div class="mt-12 flex items-center justify-center gap-4" data-aos="fade-up" data-aos-delay="1000" data-aos-offset="0">
            <x-ui::button
                priority="primary"
                class="btn-lg px-12 shadow-lg shadow-accent/20"
                :label="__('setup::wizard.complete.cta')"
                wire:click="nextStep"
                spinner
            />
        </div>
    </div>
</div>