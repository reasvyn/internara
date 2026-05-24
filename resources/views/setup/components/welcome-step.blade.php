@props(['auditResults', 'auditPassed'])

<div class="p-6 sm:p-8">
    <div class="text-center mb-8">
        <h2 class="text-xl font-bold mb-2">{{ __('setup.wizard.welcome') }}</h2>
        <p class="text-sm text-base-content/60 max-w-md mx-auto">
            {{ __('setup.wizard.welcome_desc') }}
        </p>
    </div>

    @if(!empty($auditResults['categories']))
        <div class="space-y-6 mb-8">
            @foreach($auditResults['categories'] as $key => $category)
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-base-content/40 mb-3">
                        {{ $category['label'] }}
                    </h3>

                    <div class="space-y-2">
                        @foreach($category['checks'] as $check)
                            <div @class([
                                'flex items-center gap-3 px-4 py-3 rounded-lg border text-sm',
                                'border-success/20 bg-success/5' => $check['status'] === 'pass',
                                'border-error/20 bg-error/5' => $check['status'] === 'fail',
                                'border-warning/20 bg-warning/5' => $check['status'] === 'warn',
                            ])>
                                @if($check['status'] === 'pass')
                                    <x-mary-icon name="o-check-circle" class="size-5 text-success shrink-0" />
                                @elseif($check['status'] === 'fail')
                                    <x-mary-icon name="o-x-circle" class="size-5 text-error shrink-0" />
                                @else
                                    <x-mary-icon name="o-exclamation-triangle" class="size-5 text-warning shrink-0" />
                                @endif

                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-sm">
                                        {{ __('setup.checks.' . $check['name'], $check['name_params'] ?? []) }}
                                    </p>
                                    <p class="text-xs text-base-content/50">
                                        {{ __('setup.checks.' . $check['message'], $check['message_params'] ?? []) }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="flex items-center justify-end gap-3 pt-6 border-t border-base-content/10">
        @if($auditPassed)
            <x-mary-button
                label="{{ __('setup.wizard.start_setup') }}"
                icon-right="o-arrow-right"
                class="btn-primary"
                wire:click="nextStep"
                spinner="nextStep"
            />
        @else
            <p class="text-xs text-base-content/50 mr-auto">{{ __('setup.wizard.audit_must_pass') }}</p>
            <x-mary-button
                label="{{ __('setup.wizard.recheck') }}"
                icon="o-arrow-path"
                class="btn-warning"
                wire:click="runAudit"
                spinner="runAudit"
            />
        @endif
    </div>
</div>
