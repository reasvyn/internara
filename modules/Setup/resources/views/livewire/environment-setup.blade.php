<div class="mx-auto max-w-2xl">
    <div class="mb-8 text-center">
        <p class="mb-16 font-bold text-gray-500">
            {{ __('setup::wizard.steps', ['current' => 2, 'total' => 8]) }}
        </p>

        <h2 class="text-3xl font-bold">
            {{ __('setup::wizard.environment.title') }}
        </h2>
        <p class="mt-2 text-base-content/70">
            {{ __('setup::wizard.environment.description') }}
        </p>
    </div>

    <div class="space-y-6">
        {{-- Requirements Audit --}}
        <x-mary-card title="{{ __('setup::wizard.environment.requirements') }}" separator shadow>
            <div class="space-y-3">
                @foreach($this->audit['requirements'] as $name => $passed)
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium">{{ str_replace(['extension_', '_'], ['', ' '], $name) }}</span>
                        @if($passed)
                            <x-mary-badge value="{{ __('setup::wizard.status.passed') }}" class="badge-success badge-outline" />
                        @else
                            <x-mary-badge value="{{ __('setup::wizard.status.failed') }}" class="badge-error badge-outline" />
                        @endif
                    </div>
                @endforeach
            </div>
        </x-mary-card>

        {{-- Permissions Audit --}}
        <x-mary-card title="{{ __('setup::wizard.environment.permissions') }}" separator shadow>
            <div class="space-y-3">
                @foreach($this->audit['permissions'] as $path => $writable)
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium">{{ str_replace('_', ' ', $path) }}</span>
                        @if($writable)
                            <x-mary-badge value="{{ __('setup::wizard.status.writable') }}" class="badge-success badge-outline" />
                        @else
                            <x-mary-badge value="{{ __('setup::wizard.status.not_writable') }}" class="badge-error badge-outline" />
                        @endif
                    </div>
                @endforeach
            </div>
        </x-mary-card>

        {{-- Database Audit --}}
        <x-mary-card title="{{ __('setup::wizard.environment.database') }}" separator shadow>
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-sm font-medium">{{ __('setup::wizard.environment.db_connection') }}</span>
                    @if(!$this->audit['database']['connection'])
                        <p class="mt-1 text-xs text-error">{{ $this->audit['database']['message'] }}</p>
                    @endif
                </div>
                @if($this->audit['database']['connection'])
                    <x-mary-badge value="{{ __('setup::wizard.status.connected') }}" class="badge-success badge-outline" />
                @else
                    <x-mary-badge value="{{ __('setup::wizard.status.disconnected') }}" class="badge-error badge-outline" />
                @endif
            </div>
        </x-mary-card>
    </div>

    <div class="mt-10 flex items-center justify-between">
        <x-mary-button label="{{ __('setup::wizard.buttons.back') }}" wire:click="backToPrev" class="btn-ghost" />
        <x-mary-button label="{{ __('setup::wizard.buttons.next') }}" wire:click="nextStep" class="btn-primary" :disabled="$this->disableNextStep" spinner />
    </div>
</div>
