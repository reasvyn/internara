@props(['auditResults', 'auditPassed'])

<div class="p-6 sm:p-8">
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center size-14 rounded-full bg-primary/10 text-primary mb-5" aria-hidden="true">
            <x-mary-icon name="o-rocket-launch" class="size-7" />
        </div>
        <h2 class="text-xl font-bold mb-2">{{ __('setup.wizard.welcome') }}</h2>
        <p class="text-sm text-base-content/60 max-w-md mx-auto">
            {{ __('setup.wizard.welcome_desc') }}
        </p>
    </div>

    @if(!empty($auditResults['categories']))
        <section
            aria-label="{{ __('setup.wizard.audit_results') }}"
            aria-live="polite"
            class="space-y-3 mb-8"
        >
            @foreach($auditResults['categories'] as $key => $category)
                @php
                    $hasIssue = collect($category['checks'])->contains(fn ($check) => in_array($check['status'], ['fail', 'warn']));
                @endphp

                <x-mary-collapse :open="$hasIssue" :name="'audit-'.$key">
                    <x-slot:heading>
                        <div class="flex items-center gap-2">
                            @if($hasIssue && collect($category['checks'])->contains('status', 'fail'))
                                <x-mary-icon name="o-x-circle" class="size-4 text-error shrink-0" />
                            @elseif($hasIssue)
                                <x-mary-icon name="o-exclamation-triangle" class="size-4 text-warning shrink-0" />
                            @else
                                <x-mary-icon name="o-check-circle" class="size-4 text-success shrink-0" />
                            @endif
                            <span>{{ $category['label'] }}</span>
                            <span class="text-xs text-base-content/40">({{ count($category['checks']) }})</span>
                        </div>
                    </x-slot:heading>
                    <x-slot:content>
                        <div class="space-y-2" role="list">
                            @foreach($category['checks'] as $check)
                                <div
                                    role="listitem"
                                    @class([
                                        'flex items-center gap-3 px-4 py-3 rounded-lg border text-sm transition-colors',
                                        'border-success/20 bg-success/5' => $check['status'] === 'pass',
                                        'border-error/20 bg-error/5' => $check['status'] === 'fail',
                                        'border-warning/20 bg-warning/5' => $check['status'] === 'warn',
                                    ])
                                >
                                    @php
                                        $statusLabels = [
                                            'pass' => __('setup.system.pass'),
                                            'fail' => __('setup.system.fail'),
                                            'warn' => __('setup.system.warn'),
                                        ];
                                        $statusLabel = $statusLabels[$check['status']] ?? $check['status'];
                                    @endphp

                                    <span class="sr-only">{{ $statusLabel }}</span>

                                    @if($check['status'] === 'pass')
                                        <x-mary-icon name="o-check-circle" class="size-5 text-success shrink-0" aria-hidden="true" />
                                    @elseif($check['status'] === 'fail')
                                        <x-mary-icon name="o-x-circle" class="size-5 text-error shrink-0" aria-hidden="true" />
                                    @else
                                        <x-mary-icon name="o-exclamation-triangle" class="size-5 text-warning shrink-0" aria-hidden="true" />
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
                    </x-slot:content>
                </x-mary-collapse>
            @endforeach
        </section>
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
            <div class="flex items-center gap-3 w-full" role="alert">
                <div class="flex-1 bg-warning/5 border border-warning/20 rounded-lg px-4 py-3">
                    <p class="text-xs text-warning/80 font-medium">{{ __('setup.wizard.requirements_not_met') }}</p>
                    <p class="text-xs text-warning/60 mt-0.5">{{ __('setup.wizard.audit_must_pass') }}</p>
                </div>
                <x-mary-button
                    label="{{ __('setup.wizard.recheck') }}"
                    icon="o-arrow-path"
                    class="btn-warning"
                    wire:click="runAudit"
                    spinner="runAudit"
                />
            </div>
        @endif
    </div>
</div>
