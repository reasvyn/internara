<x-setup::layouts.setup-wizard>
    <x-slot:header>
        <div>
            <x-ui::badge variant="metadata" class="mb-12">
                {{ __('setup::wizard.steps', ['current' => 2, 'total' => 8]) }}
            </x-ui::badge>

            <h1 class="text-4xl font-bold tracking-tight text-base-content">
                {{ __('setup::wizard.environment.title') }}
            </h1>

            <p class="mt-4 text-base-content/60 leading-relaxed">
                {{ __('setup::wizard.environment.description', ['app' => setting('app_name')]) }}
            </p>
        </div>

        <div class="mt-10 flex items-center gap-4">
            <x-ui::button
                variant="secondary"
                :label="__('setup::wizard.buttons.back')"
                wire:click="backToPrev"
            />
            <x-ui::button
                variant="primary"
                :label="__('setup::wizard.buttons.next')"
                wire:click="nextStep"
                :disabled="$this->disableNextStep"
                spinner
            />
        </div>
    </x-slot>

    <x-slot:content>
        <div class="space-y-6">
            {{-- Requirements Audit --}}
            <x-ui::card
                title="{{ __('setup::wizard.environment.requirements') }}"
                separator
            >
                <div class="space-y-4">
                    @foreach($this->audit['requirements'] as $name => $passed)
                        <div class="flex items-center justify-between group">
                            <span class="text-sm font-semibold text-base-content/80 group-hover:text-base-content transition-colors">
                                {{ str_replace(['extension_', '_'], ['', ' '], $name) }}
                            </span>
                            <x-ui::badge 
                                variant="custom"
                                class="{{ $passed ? 'badge-success text-success-content' : 'badge-error text-error-content' }} px-3"
                            >
                                {{ $passed ? __('setup::wizard.status.passed') : __('setup::wizard.status.failed') }}
                            </x-ui::badge>
                        </div>
                    @endforeach
                </div>
            </x-ui::card>

            {{-- Permissions Audit --}}
            <x-ui::card
                title="{{ __('setup::wizard.environment.permissions') }}"
                separator
            >
                <div class="space-y-4">
                    @foreach($this->audit['permissions'] as $path => $writable)
                        <div class="flex items-center justify-between group">
                            <span class="text-sm font-semibold text-base-content/80 group-hover:text-base-content transition-colors lowercase">
                                {{ str_replace('_', ' ', $path) }}
                            </span>
                            <x-ui::badge 
                                variant="custom"
                                class="{{ $writable ? 'badge-success text-success-content' : 'badge-error text-error-content' }} px-3"
                            >
                                {{ $writable ? __('setup::wizard.status.writable') : __('setup::wizard.status.not_writable') }}
                            </x-ui::badge>
                        </div>
                    @endforeach
                </div>
            </x-ui::card>

            {{-- Database Audit --}}
            <x-ui::card
                title="{{ __('setup::wizard.environment.database') }}"
                separator
            >
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-sm font-semibold text-base-content/80">{{ __('setup::wizard.environment.db_connection') }}</span>
                        @if(!$this->audit['database']['connection'])
                            <p class="mt-1 text-xs text-error font-medium">
                                {{ $this->audit['database']['message'] }}
                            </p>
                        @endif
                    </div>
                    <x-ui::badge 
                        variant="custom"
                        class="{{ $this->audit['database']['connection'] ? 'badge-success text-success-content' : 'badge-error text-error-content' }} px-3"
                    >
                        {{ $this->audit['database']['connection'] ? __('setup::wizard.status.connected') : __('setup::wizard.status.disconnected') }}
                    </x-ui::badge>
                </div>
            </x-ui::card>
        </div>
    </x-slot>
</x-setup::layouts.setup-wizard>