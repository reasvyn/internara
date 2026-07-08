<div>
    {{-- Progress Bar --}}
    <div class="mb-10" role="group" aria-label="{{ __('setup.wizard.progress_label') }}">
        <div class="flex items-center justify-between mb-2">
            <h1 class="text-2xl font-bold tracking-tight">{{ __('setup.wizard.title') }}</h1>
            <span class="text-xs font-medium text-base-content/50" aria-live="polite">
                {{ __('setup.wizard.step_of', ['current' => $currentStep, 'total' => count($stepKeys)]) }}
                &middot; {{ $appName }} v{{ $appVersion }}
            </span>
        </div>

        <div
            role="progressbar"
            aria-valuenow="{{ $currentStep }}"
            aria-valuemin="1"
            aria-valuemax="{{ count($stepKeys) }}"
            aria-label="{{ __('setup.wizard.progress_aria', ['current' => $currentStep, 'total' => count($stepKeys)]) }}"
            class="flex items-center gap-1"
        >
            @foreach($stepKeys as $index => $stepKey)
                @php
                    $stepNum = $index + 1;
                    $isCompleted = $stepNum < $currentStep;
                    $isCurrent = $stepNum === $currentStep;
                    $label = __('setup.wizard.step_labels.' . $stepKey);
                @endphp
                <div class="flex-1 flex items-center">
                    <div
                        @class([
                            'h-2 rounded-full flex-1 transition-colors duration-300',
                            'bg-primary' => $isCompleted || $isCurrent,
                            'bg-base-content/10' => !$isCompleted && !$isCurrent,
                        ])
                        @if($isCurrent)
                            wire:key="step-indicator-{{ $stepNum }}"
                        @endif
                        role="presentation"
                    ></div>
                    @if(!$loop->last)
                        <div class="w-px h-2 mx-0.5 bg-base-content/10" aria-hidden="true"></div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="flex items-center justify-between mt-2" aria-hidden="true">
            @foreach($stepKeys as $index => $stepKey)
                @php
                    $stepNum = $index + 1;
                    $isCompleted = $stepNum < $currentStep;
                    $isCurrent = $stepNum === $currentStep;
                    $label = __('setup.wizard.step_labels.' . $stepKey);
                @endphp
                <span @class([
                    'text-[10px] font-medium uppercase tracking-wider transition-colors',
                    'text-primary' => $isCurrent,
                    'text-base-content/30' => !$isCurrent && !$isCompleted,
                    'text-base-content/50' => $isCompleted && !$isCurrent,
                ])>
                    {{ $label }}
                </span>
            @endforeach
        </div>
    </div>

    {{-- Step Content with Transition --}}
    <div class="bg-base-100 border border-base-content/10 rounded-xl relative"
        x-data="{ loading: false }"
        x-on:finishing.window="loading = true"
        x-on:finished.window="loading = false"
    >
        <div
            wire:key="step-{{ $currentStep }}"
            x-transition:enter="transition-all duration-300 ease-out"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
        >
            @if($currentStep === 1)
                @include('setup.components.welcome-step', ['auditResults' => $audit, 'auditPassed' => $auditPassed])
            @endif

            @if($currentStep === 2)
                @include('setup.components.admin-step')
            @endif

            @if($currentStep === 3)
                @include('setup.components.school-step')
            @endif

            @if($currentStep === 4)
                @include('setup.components.department-step')
            @endif

            @if($currentStep === 5)
                @include('setup.components.finalize-step')
            @endif

            @if($currentStep === 6)
                @include('setup.components.complete-step')
            @endif
        </div>

        {{-- Loading Overlay for Finalization --}}
        <div
            wire:loading wire:target="finish"
            class="absolute inset-0 z-20 bg-base-100/90 backdrop-blur-sm rounded-xl flex items-center justify-center"
        >
            <div class="text-center p-8">
                <div class="flex items-center justify-center mb-5">
                    <div class="loading loading-spinner loading-lg text-primary"></div>
                </div>
                <h3 class="text-lg font-bold mb-2">{{ __('setup.wizard.installing_title') }}</h3>
                <p class="text-sm text-base-content/50 max-w-xs mx-auto">{{ __('setup.wizard.installing_desc') }}</p>
                <div class="flex items-center justify-center gap-1.5 mt-5">
                    <span class="size-2 rounded-full bg-primary animate-bounce" style="animation-delay: 0s"></span>
                    <span class="size-2 rounded-full bg-primary animate-bounce" style="animation-delay: 0.15s"></span>
                    <span class="size-2 rounded-full bg-primary animate-bounce" style="animation-delay: 0.3s"></span>
                </div>
            </div>
        </div>
    </div>

    @include('setup.components.setup-guide')
</div>