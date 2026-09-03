@props(['auditResults', 'auditPassed'])

<div class="p-6 sm:p-8">
    <div class="mb-8 text-center">
        <div
            class="bg-primary/10 text-primary mb-5 inline-flex size-14 items-center justify-center rounded-full"
            aria-hidden="true"
        >
            <x-ts-icon name="rocket-launch" class="size-7" />
        </div>
        <h2 class="mb-2 text-xl font-bold">{{ __('setup.wizard.welcome') }}</h2>
        <p class="text-base-content/60 mx-auto max-w-md text-sm">{{ __('setup.wizard.welcome_desc') }}</p>
    </div>

    @if (! empty($auditResults['categories']))
        <section aria-label="{{ __('setup.wizard.audit_results') }}" aria-live="polite" class="mb-8 space-y-3">
            @foreach ($auditResults['categories'] as $key => $category)
                <x-ts-accordion shadowless>
                    <x-ts-accordion.items :open="$category['has_issue']" :id="'audit-'.$key">
                        <x-slot:trigger>
                            <div class="flex items-center gap-2">
                                @if ($category['icon'] === 'fail')
                                    <x-ts-icon name="x-circle" class="text-error size-4 shrink-0" />
                                @elseif ($category['icon'] === 'warn')
                                    <x-ts-icon name="exclamation-triangle" class="text-warning size-4 shrink-0" />
                                @else
                                    <x-ts-icon name="check-circle" class="text-success size-4 shrink-0" />
                                @endif
                                <span>{{ $category['label'] }}</span>
                                <span class="text-base-content/40 text-xs">({{ count($category['checks']) }})</span>
                            </div>
                        </x-slot:trigger>
                        <div class="space-y-2" role="list">
                            @foreach ($category['checks'] as $check)
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

                                    @if ($check['status'] === 'pass')
                                        <x-ts-icon
                                            name="check-circle"
                                            class="text-success size-5 shrink-0"
                                            aria-hidden="true"
                                        />
                                    @elseif ($check['status'] === 'fail')
                                        <x-ts-icon
                                            name="x-circle"
                                            class="text-error size-5 shrink-0"
                                            aria-hidden="true"
                                        />
                                    @else
                                        <x-ts-icon
                                            name="exclamation-triangle"
                                            class="text-warning size-5 shrink-0"
                                            aria-hidden="true"
                                        />
                                    @endif

                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium">
                                            {{ __('setup.checks.' . $check['name'], $check['name_params'] ?? []) }}
                                        </p>
                                        <p class="text-base-content/50 text-xs">
                                            {{ __('setup.checks.' . $check['message'], $check['message_params'] ?? []) }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-ts-accordion.items>
                </x-ts-accordion>

            @endforeach
        </section>
    @endif

    <div class="border-base-content/10 flex items-center justify-end gap-3 border-t pt-6">
        @if ($auditPassed)
            <x-ts-button
                text="{{ __('setup.wizard.start_setup') }}"
                icon-right="arrow-right"
                color="primary"
                wire:click="nextStep"
                loading="nextStep"
            />
        @else
            <div class="flex w-full items-center gap-3" role="alert">
                <div class="bg-warning/5 border-warning/20 flex-1 rounded-lg border px-4 py-3">
                    <p class="text-warning/80 text-xs font-medium">{{ __('setup.wizard.requirements_not_met') }}</p>
                    <p class="text-warning/60 mt-0.5 text-xs">{{ __('setup.wizard.audit_must_pass') }}</p>
                </div>
                <x-ts-button
                    text="{{ __('setup.wizard.recheck') }}"
                    icon="arrow-path"
                    color="yellow"
                    wire:click="runAudit"
                    loading="runAudit"
                />
            </div>
        @endif
    </div>
</div>
