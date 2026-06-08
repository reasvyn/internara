<div>
    {{-- Progress Bar --}}
    <div class="mb-10">
        <div class="flex items-center justify-between mb-2">
            <h1 class="text-2xl font-bold tracking-tight">{{ __('setup.wizard.title') }}</h1>
            <span class="text-xs font-medium text-base-content/50">
                {{ __('setup.wizard.step_of', ['current' => $currentStep]) }}
                &middot; {{ $appName }} v{{ $appVersion }}
            </span>
        </div>

        <div class="flex items-center gap-1">
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
                    ></div>
                    @if(!$loop->last)
                        <div class="w-px h-2 mx-0.5 bg-base-content/10"></div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="flex items-center justify-between mt-2">
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

    {{-- Step Content --}}
    <div class="bg-base-100 border border-base-content/10 rounded-xl">
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

    @include('setup.components.setup-guide')
</div>