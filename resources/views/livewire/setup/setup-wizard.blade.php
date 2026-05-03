<div>
    <div class="max-w-5xl mx-auto py-16 px-6 lg:px-12">
        {{-- Progress Header (Modern Minimalist) --}}
        <div class="mb-16">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
                <div>
                    <h1 class="text-4xl md:text-5xl font-black tracking-tightest text-base-content uppercase leading-none">
                        Installation <span class="text-primary">Wizard</span>
                    </h1>
                    <p class="mt-3 text-sm font-bold text-base-content/40 uppercase tracking-[0.3em]">
                        {{ __('setup.wizard.step_of', ['current' => $currentStep]) }} • {{ $appName }} v{{ $appVersion }}
                    </p>
                </div>
            </div>

            {{-- Step Navigation Dots (Advanced Visuals) --}}
            <div class="flex items-center justify-between gap-3">
                @php
                    $stepKeys = \App\Services\Setup\SetupService::STEPS;
                @endphp
                @foreach($stepKeys as $index => $stepKey)
                    @php
                        $stepNum = $index + 1;
                        $isCompleted = $stepNum < $currentStep;
                        $isCurrent = $stepNum === $currentStep;
                        $label = __('setup.wizard.step_labels.' . $stepKey);
                    @endphp
                    <button 
                        wire:click="goToStep('{{ $stepKey }}')"
                        @disabled($stepNum > $currentStep && !app(\App\Services\Setup\SetupService::class)->isStepCompleted($stepKey))
                        @class([
                            'flex-1 group transition-all duration-700 outline-none',
                            'opacity-40 hover:opacity-100' => !$isCurrent && !$isCompleted,
                            'scale-[1.02]' => $isCurrent
                        ])
                    >
                        <div @class([
                            'h-2 rounded-full transition-all duration-700 relative overflow-hidden',
                            'bg-primary' => $isCompleted || $isCurrent,
                            'bg-base-content/10' => !$isCompleted && !$isCurrent
                        ])>
                            @if($isCurrent)
                                <div class="absolute inset-0 bg-white/20 animate-[shimmer_2s_infinite]"></div>
                                <div class="absolute inset-0 shadow-[0_0_15px_rgba(var(--color-primary),0.6)]"></div>
                            @endif
                        </div>
                        <div @class([
                            'mt-4 text-[10px] font-black uppercase tracking-[0.2em] transition-all duration-500 hidden md:block',
                            'text-primary' => $isCurrent,
                            'text-base-content/30 group-hover:text-base-content' => !$isCurrent
                        ])>
                            {{ $label }}
                        </div>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Main Container with Entrance Animation --}}
        <div class="animate-in fade-in slide-in-from-bottom-8 duration-1000">
            <x-mary-card shadow class="card-enterprise !bg-base-100 shadow-2xl shadow-base-content/5 border border-base-content/5 overflow-visible">
                {{-- Step 1: Welcome & System Requirements --}}
                @if($currentStep === 1)
                    <livewire:setup.components.welcome-step :$auditResults :$auditPassed />
                @endif

                {{-- Step 2: School Information --}}
                @if($currentStep === 2)
                    @include('livewire.setup.components.school-step')
                @endif

                {{-- Step 3: Super Admin Account --}}
                @if($currentStep === 3)
                    @include('livewire.setup.components.admin-step')
                @endif

                {{-- Step 4: Department --}}
                @if($currentStep === 4)
                    @include('livewire.setup.components.department-step')
                @endif

                {{-- Step 5: Internship Program --}}
                @if($currentStep === 5)
                    @include('livewire.setup.components.internship-step')
                @endif

                {{-- Step 6: Finalize --}}
                @if($currentStep === 6)
                    @include('livewire.setup.components.finalize-step')
                @endif

                {{-- Step 7: Complete --}}
                @if($currentStep === 7)
                    @include('livewire.setup.components.complete-step')
                @endif
            </x-mary-card>
        </div>
    </div>
</div>
