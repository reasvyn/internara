<x-setup::layouts.setup-wizard>
    <x-slot:header>
        <div class="max-w-4xl">
            <x-ui::badge variant="metadata" class="mb-12">
                {{ __('setup::wizard.steps', ['current' => 4, 'total' => 8]) }}
            </x-ui::badge>

            <h1 class="text-4xl font-extrabold tracking-tight text-base-content md:text-5xl lg:text-6xl leading-[1.1]">
                {{ __('setup::wizard.account.headline') }}
            </h1>

            <div class="mt-8 space-y-6">
                <p class="text-lg text-base-content/70 leading-relaxed max-w-2xl">
                    {{ __('setup::wizard.account.description', ['app' => setting('app_name')]) }}
                </p>
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary/5 border border-primary/10">
                    <x-ui::icon name="tabler.shield-check" class="size-4 text-primary" />
                    <span class="text-xs font-bold uppercase tracking-widest text-primary">
                        {{ __('auth::ui.register_super_admin.sovereign_label') }}
                    </span>
                </div>
            </div>
        </div>

        <div 
            class="mt-12 flex flex-wrap items-center gap-4"
            x-data="{ canContinue: @json($this->isRecordExists) }"
            @super_admin_registered.window="canContinue = true"
        >
            <x-ui::button
                variant="secondary"
                :label="__('setup::wizard.common.back')"
                wire:click="backToPrev"
            />
            <x-ui::button
                variant="primary"
                class="btn-lg px-12 shadow-lg shadow-primary/20"
                :label="__('setup::wizard.common.continue')"
                wire:click="nextStep"
                x-bind:disabled="!canContinue"
                spinner
            />
        </div>
    </x-slot>

    <x-slot:content>
        <div class="w-full">
            <x-ui::honeypot wire:model="contact_me" />
            <x-ui::turnstile wire:model="turnstile" class="mb-8" />
            
            <div class="bg-base-100 rounded-3xl p-8 md:p-12 shadow-sm border border-base-content/5">
                @slotRender('register.super-admin')
            </div>
        </div>
    </x-slot>
</x-setup::layouts.setup-wizard>