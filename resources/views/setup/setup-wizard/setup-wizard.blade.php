<div>
    {{-- Progress Stepper Header --}}
    <div class="mb-8" role="group" aria-label="{{ __('setup.wizard.progress_label') }}">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-base-content text-2xl font-black tracking-tight sm:text-3xl">
                    {{ __('setup.wizard.title') }}
                </h1>
                <p class="text-base-content/50 mt-1 text-xs font-medium">
                    {{ $appName }}
                    <span class="bg-base-content/10 text-base-content/70 rounded-md px-1.5 py-0.5 font-mono text-[11px]">v{{ $appVersion }}</span>
                </p>
            </div>
            <div
                class="border-base-content/10 bg-base-100/90 text-base-content/70 inline-flex items-center gap-2 rounded-full border px-4 py-1.5 text-xs font-semibold shadow-2xs backdrop-blur-xs"
                aria-live="polite"
            >
                <span class="bg-primary size-2 animate-pulse rounded-full"></span>
                {{ __('setup.wizard.step_of', ['current' => $currentStep, 'total' => count($stepKeys)]) }}
            </div>
        </div>

        @php
            $stepIcons = [
                'welcome' => 'sparkles',
                'account' => 'shield-check',
                'school' => 'academic-cap',
                'department' => 'building-library',
                'finalize' => 'document-check',
                'complete' => 'check-badge',
            ];
        @endphp

        <div
            role="progressbar"
            aria-valuenow="{{ $currentStep }}"
            aria-valuemin="1"
            aria-valuemax="{{ count($stepKeys) }}"
            aria-label="{{ __('setup.wizard.progress_aria', ['current' => $currentStep, 'total' => count($stepKeys)]) }}"
            class="flex items-center justify-between"
        >
            @foreach ($stepKeys as $index => $stepKey)
                @php
                    $stepNum = $index + 1;
                    $isCompleted = $stepNum < $currentStep;
                    $isCurrent = $stepNum === $currentStep;
                    $label = __('setup.wizard.step_labels.'.$stepKey);
                    $icon = $stepIcons[$stepKey] ?? 'circle-stack';
                @endphp
                <div class="flex flex-1 flex-col items-center gap-2">
                    <div
                        @class([
                            'size-10 rounded-2xl flex items-center justify-center text-xs font-bold transition-all duration-300 shrink-0',
                            'bg-primary text-primary-content shadow-xs ring-4 ring-primary/10' => $isCompleted,
                            'bg-primary text-primary-content ring-4 ring-primary/25 shadow-md scale-105' => $isCurrent,
                            'bg-base-100 text-base-content/40 border border-base-content/15 shadow-2xs' => ! $isCompleted && ! $isCurrent,
                        ])
                        @if ($isCurrent)
                            wire:key="step-indicator-{{ $stepNum }}"
                        @endif
                        role="status"
                    >
                        @if ($isCompleted)
                            <x-ts-icon name="check" class="size-4 stroke-[2.5]" />
                        @elseif ($isCurrent)
                            <x-ts-icon :name="$icon" class="size-4" />
                        @else
                            <span class="font-mono text-xs">{{ $stepNum }}</span>
                        @endif
                    </div>
                    <span @class([
                        'text-[11px] font-bold tracking-tight transition-colors text-center leading-tight hidden sm:block',
                        'text-primary' => $isCurrent,
                        'text-base-content/70' => $isCompleted,
                        'text-base-content/40' => ! $isCurrent && ! $isCompleted,
                    ])>
                        {{ $label }}
                    </span>
                </div>
                @if (! $loop->last)
                    <div
                        @class([
                            'flex-1 h-0.5 mx-2 transition-colors duration-300 sm:-mt-6 -mt-1 rounded-full',
                            'bg-primary' => $isCompleted,
                            'bg-base-content/10' => ! $isCompleted,
                        ])
                        aria-hidden="true"
                    ></div>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Step Content Card --}}
    <div
        class="border-base-content/10 bg-base-100 relative overflow-hidden rounded-3xl border shadow-sm backdrop-blur-xs"
        x-data="{ loading: false }"
        x-on:finishing.window="loading = true"
        x-on:finished.window="loading = false"
    >
        {{-- Card Top Decorative Accent --}}
        <div class="from-primary via-primary/80 to-primary/40 h-1.5 w-full bg-linear-to-r" aria-hidden="true"></div>

        <div
            wire:key="step-{{ $currentStep }}"
            x-transition:enter="transition-all duration-300 ease-out"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
        >
            @if ($currentStep === 1)
                @include('setup.components.welcome-step', ['auditResults' => $audit, 'auditPassed' => $auditPassed])
            @endif

            @if ($currentStep === 2)
                @include('setup.components.admin-step')
            @endif

            @if ($currentStep === 3)
                @include('setup.components.school-step')
            @endif

            @if ($currentStep === 4)
                @include('setup.components.department-step')
            @endif

            @if ($currentStep === 5)
                @include('setup.components.finalize-step')
            @endif

            @if ($currentStep === 6)
                @include('setup.components.complete-step')
            @endif
        </div>

        {{-- Loading Overlay for Finalization --}}
        <div
            wire:loading
            wire:target="finish"
            class="bg-base-100/90 absolute inset-0 z-20 flex items-center justify-center rounded-3xl backdrop-blur-md"
        >
            <div class="p-8 text-center">
                <div class="mb-5 flex items-center justify-center">
                    <svg class="text-primary size-12 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <h3 class="text-base-content mb-2 text-xl font-bold">{{ __('setup.wizard.installing_title') }}</h3>
                <p class="text-base-content/60 mx-auto max-w-xs text-sm leading-relaxed">
                    {{ __('setup.wizard.installing_desc') }}
                </p>
                <div class="mt-6 flex items-center justify-center gap-2">
                    <span class="bg-primary size-2 animate-bounce rounded-full" style="animation-delay: 0s"></span>
                    <span class="bg-primary size-2 animate-bounce rounded-full" style="animation-delay: 0.15s"></span>
                    <span class="bg-primary size-2 animate-bounce rounded-full" style="animation-delay: 0.3s"></span>
                </div>
            </div>
        </div>
    </div>

    @include('setup.components.setup-guide')
</div>
