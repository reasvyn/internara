@props(['auditResults', 'auditPassed'])

<div class="p-6 sm:p-10 lg:p-12">
    <div class="mb-10 text-center">
        <div
            class="border-primary/20 bg-primary/10 text-primary ring-primary/10 mx-auto mb-5 flex size-18 items-center justify-center rounded-3xl border shadow-xs ring-4"
            aria-hidden="true"
        >
            <x-ts-icon name="rocket-launch" class="size-9" />
        </div>
        <h2 class="text-base-content mb-2 text-2xl font-black tracking-tight sm:text-3xl">
            {{ __('setup.wizard.welcome') }}
        </h2>
        <p class="text-base-content/60 mx-auto max-w-lg text-sm leading-relaxed">
            {{ __('setup.wizard.welcome_desc') }}
        </p>
    </div>

    @if ($auditPassed)
        <div class="border-success/20 bg-success/5 mb-8 flex flex-wrap items-center justify-between gap-4 rounded-2xl border p-4.5 sm:p-5">
            <div class="flex items-center gap-3.5">
                <div class="border-success/20 bg-success/10 text-success flex size-10 shrink-0 items-center justify-center rounded-xl border">
                    <x-ts-icon name="check-badge" class="size-5" />
                </div>
                <div>
                    <p class="text-base-content text-sm font-bold">{{ __('setup.wizard.system_requirements') }}</p>
                    <p class="text-base-content/60 text-xs">
                        {{ __('setup.system.pass') }} — {{ __('setup.wizard.all_requirements_met') }}
                    </p>
                </div>
            </div>
            <span class="border-success/20 bg-success/10 text-success inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold">
                <span class="bg-success size-1.5 animate-pulse rounded-full"></span>
                {{ __('setup.wizard.fully_ready') }}
            </span>
        </div>
    @endif

    @if (! empty($auditResults['categories']))
        <section aria-label="{{ __('setup.wizard.audit_results') }}" aria-live="polite" class="mb-8 space-y-3">
            @foreach ($auditResults['categories'] as $key => $category)
                <x-ts-accordion shadowless class="border-base-content/10 overflow-hidden rounded-2xl border">
                    <x-ts-accordion.items :open="$category['has_issue']" :id="'audit-'.$key">
                        <x-slot:trigger>
                            <div class="flex items-center gap-3 py-1">
                                @if ($category['icon'] === 'fail')
                                    <div class="border-error/20 bg-error/10 text-error flex size-7 shrink-0 items-center justify-center rounded-lg border">
                                        <x-ts-icon name="x-mark" class="size-4 stroke-[2.5]" />
                                    </div>
                                @elseif ($category['icon'] === 'warn')
                                    <div class="border-warning/20 bg-warning/10 text-warning flex size-7 shrink-0 items-center justify-center rounded-lg border">
                                        <x-ts-icon name="exclamation-triangle" class="size-4" />
                                    </div>
                                @else
                                    <div class="border-success/20 bg-success/10 text-success flex size-7 shrink-0 items-center justify-center rounded-lg border">
                                        <x-ts-icon name="check" class="size-4 stroke-[2.5]" />
                                    </div>
                                @endif
                                <span class="text-base-content text-sm font-bold">{{ $category['label'] }}</span>
                                <span class="border-base-content/10 bg-base-200/60 text-base-content/60 rounded-full border px-2 py-0.5 font-mono text-[11px] font-semibold">
                                    {{ count($category['checks']) }}
                                </span>
                            </div>
                        </x-slot:trigger>
                        <div class="space-y-2.5 pt-2" role="list">
                            @foreach ($category['checks'] as $check)
                                <div
                                    role="listitem"
                                    @class([
                                        'flex items-center justify-between gap-3 px-4 py-3 rounded-xl border text-sm transition-all',
                                        'border-base-content/10 bg-base-200/30 hover:bg-base-200/50' => $check['status'] === 'pass',
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

                                    <div class="flex min-w-0 flex-1 items-center gap-3">
                                        @if ($check['status'] === 'pass')
                                            <div class="border-success/20 bg-success/10 text-success flex size-6 shrink-0 items-center justify-center rounded-full border">
                                                <x-ts-icon
                                                    name="check"
                                                    class="size-3.5 stroke-[2.5]"
                                                    aria-hidden="true"
                                                />
                                            </div>
                                        @elseif ($check['status'] === 'fail')
                                            <div class="border-error/20 bg-error/10 text-error flex size-6 shrink-0 items-center justify-center rounded-full border">
                                                <x-ts-icon
                                                    name="x-mark"
                                                    class="size-3.5 stroke-[2.5]"
                                                    aria-hidden="true"
                                                />
                                            </div>
                                        @else
                                            <div class="border-warning/20 bg-warning/10 text-warning flex size-6 shrink-0 items-center justify-center rounded-full border">
                                                <x-ts-icon
                                                    name="exclamation-triangle"
                                                    class="size-3.5"
                                                    aria-hidden="true"
                                                />
                                            </div>
                                        @endif

                                        <div class="min-w-0 flex-1">
                                            <p class="text-base-content text-xs font-semibold sm:text-sm">
                                                {{ __('setup.checks.' . $check['name'], $check['name_params'] ?? []) }}
                                            </p>
                                            <p class="text-base-content/50 text-[11px] sm:text-xs">
                                                {{ __('setup.checks.' . $check['message'], $check['message_params'] ?? []) }}
                                            </p>
                                        </div>
                                    </div>

                                    <span class="sr-only">{{ $statusLabel }}</span>

                                    @if ($check['status'] === 'pass')
                                        <span class="border-success/20 bg-success/10 text-success shrink-0 rounded-md border px-2 py-0.5 text-[11px] font-bold">
                                            {{ __('setup.system.pass') }}
                                        </span>
                                    @elseif ($check['status'] === 'fail')
                                        <span class="border-error/20 bg-error/10 text-error shrink-0 rounded-md border px-2 py-0.5 text-[11px] font-bold">
                                            {{ __('setup.system.fail') }}
                                        </span>
                                    @else
                                        <span class="border-warning/20 bg-warning/10 text-warning shrink-0 rounded-md border px-2 py-0.5 text-[11px] font-bold">
                                            {{ __('setup.system.warn') }}
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </x-ts-accordion.items>
                </x-ts-accordion>
            @endforeach
        </section>
    @endif

    <div class="border-base-content/10 mt-10 flex items-center justify-end gap-3 border-t pt-6">
        @if ($auditPassed)
            <x-ts-button
                :text="__('setup.wizard.start_setup')"
                icon="arrow-right"
                position="right"
                color="primary"
                wire:click="nextStep"
                loading="nextStep"
            />
        @else
            <div class="flex w-full items-center gap-3" role="alert">
                <div class="bg-warning/5 border-warning/20 flex-1 rounded-2xl border px-4 py-3">
                    <p class="text-warning/80 text-xs font-medium">{{ __('setup.wizard.requirements_not_met') }}</p>
                    <p class="text-warning/60 mt-0.5 text-xs">{{ __('setup.wizard.audit_must_pass') }}</p>
                </div>
                <x-ts-button
                    :text="__('setup.wizard.recheck')"
                    icon="arrow-path"
                    color="yellow"
                    wire:click="runAudit"
                    loading="runAudit"
                />
            </div>
        @endif
    </div>
</div>
