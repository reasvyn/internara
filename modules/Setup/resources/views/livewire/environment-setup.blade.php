<x-setup::layouts.setup-wizard>
    <x-slot:header>
        <div>
            <x-ui::badge variant="metadata" class="mb-12">
                {{ __('setup::wizard.steps', ['current' => 2, 'total' => 8]) }}
            </x-ui::badge>

            <h1 class="text-4xl font-bold tracking-tight text-base-content md:text-5xl">
                {{ __('setup::wizard.environment.title') }}
            </h1>

            <p class="mt-6 text-lg text-base-content/60 leading-relaxed max-w-2xl">
                {{ __('setup::wizard.environment.description') }}
            </p>
        </div>

        <div class="mt-10 flex flex-wrap items-center gap-4">
            <x-ui::button
                variant="secondary"
                :label="__('setup::wizard.buttons.back')"
                wire:click="backToPrev"
            />
            <x-ui::button
                variant="secondary"
                class="border-primary/50 text-primary hover:bg-primary/5"
                :label="__('setup::wizard.environment.refresh')"
                wire:click="refreshAudit"
                spinner="refreshAudit"
                icon="tabler.refresh"
            />
            <x-ui::button
                variant="primary"
                :label="__('setup::wizard.buttons.next')"
                wire:click="nextStep"
                :disabled="$this->disableNextStep"
                spinner="nextStep"
            />
        </div>
    </x-slot>

    <x-slot:content>
        <div class="space-y-8">
            {{-- Requirements Audit --}}
            <x-ui::card
                title="{{ __('setup::wizard.environment.requirements') }}"
                subtitle="{{ __('setup::wizard.environment.requirements_desc') }}"
                separator
            >
                <div class="space-y-4">
                    @foreach($this->audit['requirements'] as $label => $passed)
                        <div class="flex items-center justify-between group py-1">
                            <span class="text-sm font-bold tracking-wide text-base-content/80 group-hover:text-base-content transition-colors">
                                {{ $label }}
                            </span>
                            <div class="flex items-center gap-3">
                                @if($passed)
                                    <x-ui::icon name="tabler.circle-check-filled" class="text-success size-5" />
                                @else
                                    <x-ui::icon name="tabler.circle-x-filled" class="text-error size-5" />
                                @endif
                                <x-ui::badge 
                                    variant="custom"
                                    class="{{ $passed ? 'bg-success/10 text-success border-success/20' : 'bg-error/10 text-error border-error/20' }} border px-3 font-bold text-[10px] uppercase tracking-widest"
                                >
                                    {{ $passed ? __('setup::wizard.status.passed') : __('setup::wizard.status.failed') }}
                                </x-ui::badge>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui::card>

            {{-- Permissions Audit --}}
            <x-ui::card
                title="{{ __('setup::wizard.environment.permissions') }}"
                subtitle="{{ __('setup::wizard.environment.permissions_desc') }}"
                separator
            >
                <div class="space-y-4">
                    @foreach($this->audit['permissions'] as $label => $writable)
                        <div class="flex items-center justify-between group py-1">
                            <span class="text-sm font-bold tracking-wide text-base-content/80 group-hover:text-base-content transition-colors">
                                {{ $label }}
                            </span>
                            <div class="flex items-center gap-3">
                                @if($writable)
                                    <x-ui::icon name="tabler.circle-check-filled" class="text-success size-5" />
                                @else
                                    <x-ui::icon name="tabler.circle-x-filled" class="text-error size-5" />
                                @endif
                                <x-ui::badge 
                                    variant="custom"
                                    class="{{ $writable ? 'badge-success/10 text-success' : 'badge-error/10 text-error' }} px-3 font-bold text-[10px] uppercase tracking-widest"
                                >
                                    {{ $writable ? __('setup::wizard.status.writable') : __('setup::wizard.status.not_writable') }}
                                </x-ui::badge>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui::card>

            {{-- Database Audit --}}
            <x-ui::card
                title="{{ __('setup::wizard.environment.database') }}"
                subtitle="{{ __('setup::wizard.environment.database_desc') }}"
                separator
            >
                <div class="flex items-center justify-between group">
                    <div class="max-w-[70%]">
                        <span class="text-sm font-bold tracking-wide text-base-content/80 group-hover:text-base-content">{{ __('setup::wizard.environment.db_connection') }}</span>
                        @if(!$this->audit['database']['connection'])
                            <p class="mt-2 text-xs text-error font-medium leading-relaxed bg-error/5 p-3 rounded-lg border border-error/20">
                                {{ $this->audit['database']['message'] }}
                            </p>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        @if($this->audit['database']['connection'])
                            <x-ui::icon name="tabler.circle-check-filled" class="text-success size-5" />
                        @else
                            <x-ui::icon name="tabler.circle-x-filled" class="text-error size-5" />
                        @endif
                        <x-ui::badge 
                            variant="custom"
                            class="{{ $this->audit['database']['connection'] ? 'badge-success/10 text-success' : 'badge-error/10 text-error' }} px-3 font-bold text-[10px] uppercase tracking-widest"
                        >
                            {{ $this->audit['database']['connection'] ? __('setup::wizard.status.connected') : __('setup::wizard.status.disconnected') }}
                        </x-ui::badge>
                    </div>
                </div>
            </x-ui::card>
        </div>
    </x-slot>
</x-setup::layouts.setup-wizard>