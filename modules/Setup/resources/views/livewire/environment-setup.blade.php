<x-setup::layouts.setup-wizard>
    <x-slot:header>
        <p class="mb-16 font-bold text-gray-500">
            {{ __('setup::wizard.steps', ['current' => 2, 'total' => 8]) }}
        </p>

        <h1 class="text-3xl font-bold">{{ __('setup::wizard.environment.title') }}</h1>

        <p class="mt-4 text-base-content/70">
            {{ __('setup::wizard.environment.description') }}
        </p>

        <div class="mt-10 flex items-center gap-4">
            <x-ui::button
                :label="__('setup::wizard.buttons.back')"
                wire:click="backToPrev"
                class="btn-secondary btn-outline"
            />
            <x-ui::button
                :label="__('setup::wizard.buttons.next')"
                wire:click="nextStep"
                class="btn-primary"
                :disabled="$this->disableNextStep"
                spinner
            />
        </div>
    </x-slot>

    <x-slot:content>
        <div class="space-y-6">
            {{-- Requirements Audit --}}
            <x-mary-card
                title="{{ __('setup::wizard.environment.requirements') }}"
                separator
                shadow
            >
                <div class="space-y-3">
                    @foreach($this->audit['requirements'] as $name => $passed)
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">{{ str_replace(['extension_', '_'], ['', ' '], $name) }}</span>
                            @if($passed)
                                <x-mary-badge
                                    value="{{ __('setup::wizard.status.passed') }}"
                                    class="badge-success"
                                />
                            @else
                                <x-mary-badge
                                    value="{{ __('setup::wizard.status.failed') }}"
                                    class="badge-error"
                                />
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-mary-card>

            {{-- Permissions Audit --}}
            <x-mary-card
                title="{{ __('setup::wizard.environment.permissions') }}"
                separator
                shadow
            >
                <div class="space-y-3">
                    @foreach($this->audit['permissions'] as $path => $writable)
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">{{ str_replace('_', ' ', $path) }}</span>
                            @if($writable)
                                <x-mary-badge
                                    value="{{ __('setup::wizard.status.writable') }}"
                                    class="badge-success"
                                />
                            @else
                                <x-mary-badge
                                    value="{{ __('setup::wizard.status.not_writable') }}"
                                    class="badge-error"
                                />
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-mary-card>

            {{-- Database Audit --}}
            <x-mary-card
                title="{{ __('setup::wizard.environment.database') }}"
                separator
                shadow
            >
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-sm font-medium">{{ __('setup::wizard.environment.db_connection') }}</span>
                        @if(!$this->audit['database']['connection'])
                            <p class="mt-1 text-xs text-error">
                                {{ $this->audit['database']['message'] }}
                            </p>
                        @endif
                    </div>
                    @if($this->audit['database']['connection'])
                        <x-mary-badge
                            value="{{ __('setup::wizard.status.connected') }}"
                            class="badge-success"
                        />
                    @else
                        <x-mary-badge
                            value="{{ __('setup::wizard.status.disconnected') }}"
                            class="badge-error"
                        />
                    @endif
                </div>
            </x-mary-card>
        </div>
    </x-slot>
</x-setup::layouts.setup-wizard>
