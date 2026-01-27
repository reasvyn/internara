<div class="container mx-auto flex flex-col items-center justify-center gap-8 text-center">
    <div class="max-w-prose text-center">
        <p class="mb-16 font-bold text-success">{{ __('setup::wizard.complete.badge') }}</p>

        <h1 class="text-3xl font-bold">{{ __('setup::wizard.complete.headline') }}</h1>

        <p class="mt-4">
            {{ __('setup::wizard.complete.description') }}
        </p>
        <p class="mt-2">
            {{ __('setup::wizard.complete.description_extra') }}
        </p>

        <div class="mt-8 flex items-center gap-4">
            <x-ui::button
                class="btn-primary"
                :label="__('setup::wizard.complete.cta')"
                wire:click="nextStep"
            />
        </div>
    </div>
</div>