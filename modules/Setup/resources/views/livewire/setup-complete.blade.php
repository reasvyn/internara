<div class="container mx-auto flex flex-col items-center justify-center gap-8 text-center py-12">
    <div class="max-w-2xl text-center">
        <x-ui::badge variant="metadata" :value="__('setup::wizard.steps', ['current' => 8, 'total' => 8])" class="mb-12" />

        <p class="mb-6 font-bold text-success text-lg">
            {{ __('setup::wizard.complete.badge') }}
        </p>

        <h1 class="text-4xl font-bold tracking-tight text-base-content md:text-5xl">
            {{ __('setup::wizard.complete.headline', ['app' => setting('app_name', 'Internara')]) }}
        </h1>

        <div class="mt-8 space-y-4">
            <p class="text-base-content/70 leading-relaxed text-lg">
                {{ __('setup::wizard.complete.description', ['app' => setting('app_name', 'Internara')]) }}
            </p>
            <p class="text-base-content/60 leading-relaxed">
                {{ __('setup::wizard.complete.description_extra') }}
            </p>
        </div>

        <div class="mt-12 flex items-center justify-center gap-4">
            <x-ui::button
                variant="primary"
                class="btn-lg px-12 shadow-lg shadow-primary/20"
                :label="__('setup::wizard.complete.cta')"
                wire:click="nextStep"
                spinner
            />
        </div>
    </div>
</div>