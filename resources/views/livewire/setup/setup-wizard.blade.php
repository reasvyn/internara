<div>
    <div class="max-w-4xl mx-auto py-12 px-4">
        {{-- Progress Header with Dot Navigation (Legacy Parity) --}}
        <div class="mb-10">
            <div class="flex items-center justify-between mb-4 px-2">
                <div>
                    <h1 class="text-3xl font-black tracking-tight text-primary uppercase">
                        {{ $appName }} <span class="text-base-content/30">/</span> {{ __('setup.wizard.title') }}
                    </h1>
                </div>
                <div class="text-right">
                    <span class="text-xs font-bold uppercase tracking-widest text-base-content/40">
                        {{ __('setup.wizard.step_of', ['current' => $currentStep]) }}
                    </span>
                </div>
            </div>

            {{-- Step Navigation Dots --}}
            <div class="flex items-center justify-between gap-2 px-2">
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
                        class="flex-1 group"
                    >
                        <div class="h-1.5 rounded-full transition-all duration-500 {{ $isCompleted ? 'bg-primary' : ($isCurrent ? 'bg-primary shadow-[0_0_10px_rgba(var(--p),0.5)]' : 'bg-base-300') }}"></div>
                        <div class="mt-2 text-[10px] font-black uppercase tracking-tighter transition-colors {{ $isCurrent ? 'text-primary' : 'text-base-content/30' }} hidden md:block">
                            {{ $label }}
                        </div>
                    </button>
                @endforeach
            </div>
        </div>

        <x-mary-card shadow class="bg-base-100 border-none shadow-xl rounded-3xl overflow-hidden">
            {{-- Step1: Welcome & System Requirements --}}
            @if($currentStep === 1)
                <div class="p-4 md:p-8">
                    <div class="text-center py-10">
                        <div class="inline-flex items-center justify-center size-24 rounded-3xl bg-primary/10 text-primary mb-6">
                            <x-mary-icon name="o-rocket-launch" class="size-12" />
                        </div>
                        <h2 class="text-3xl font-black tracking-tight mb-3">{{ __('setup.wizard.welcome') }}</h2>
                        <p class="text-base-content/60 max-w-md mx-auto leading-relaxed">
                            {{ __('setup.wizard.welcome_desc') }}
                        </p>
                    </div>

                    <div class="space-y-10 mb-10">
                        @foreach($auditResults['categories'] as $key => $category)
                            <div>
                                <div class="divider uppercase text-[10px] font-bold tracking-[0.2em] opacity-30 mb-6">{{ $category['label'] }}</div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    @foreach($category['checks'] as $check)
                                        <div class="flex items-center gap-4 p-4 rounded-2xl border border-base-200 {{ $check['status'] === 'fail' ? 'bg-error/5 border-error/20' : ($check['status'] === 'warn' ? 'bg-warning/5 border-warning/20' : 'bg-base-100') }}">
                                            <div class="shrink-0">
                                                @if($check['status'] === 'pass')
                                                    <div class="size-8 rounded-full bg-success/20 text-success flex items-center justify-center">
                                                        <x-mary-icon name="o-check" class="size-4" />
                                                    </div>
                                                @elseif($check['status'] === 'fail')
                                                    <div class="size-8 rounded-full bg-error/20 text-error flex items-center justify-center">
                                                        <x-mary-icon name="o-x-mark" class="size-4" />
                                                    </div>
                                                @else
                                                    <div class="size-8 rounded-full bg-warning/20 text-warning flex items-center justify-center">
                                                        <x-mary-icon name="o-exclamation-triangle" class="size-4" />
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <span class="text-xs font-black uppercase tracking-wide block leading-none mb-1">{{ $check['name'] }}</span>
                                                <p class="text-[11px] text-base-content/50 truncate">{{ $check['message'] }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex justify-end">
                        @if($auditPassed)
                            <x-mary-button label="{{ __('setup.wizard.start_setup') }}" icon-right="o-arrow-long-right" class="btn-primary btn-wide rounded-2xl font-black uppercase tracking-widest" wire:click="nextStep" />
                        @else
                            <x-mary-button label="{{ __('setup.wizard.recheck') }}" icon="o-arrow-path" class="btn-warning rounded-2xl font-black uppercase tracking-widest" wire:click="runAudit" spinner="runAudit" />
                        @endif
                    </div>
                </div>
            @endif

            {{-- Step 2: School Information --}}
            @if($currentStep === 2)
                <div class="p-4 md:p-8">
                    <div class="flex items-center gap-4 mb-8">
                        <div class="size-12 rounded-2xl bg-base-200 flex items-center justify-center text-primary">
                            <x-mary-icon name="o-academic-cap" class="size-6" />
                        </div>
                        <div>
                            <h2 class="text-2xl font-black tracking-tight">{{ __('setup.wizard.school_info') }}</h2>
                            <p class="text-xs text-base-content/50 uppercase font-bold tracking-widest">{{ __('setup.wizard.school_subtitle') }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-mary-input label="{{ __('setup.wizard.school_name') }}" wire:model.live="schoolName" placeholder="{{ __('setup.wizard.school_name_placeholder') }}" class="rounded-xl border-base-300" />
                        <x-mary-input label="{{ __('setup.wizard.school_code') }}" wire:model.live="schoolCode" placeholder="{{ __('setup.wizard.school_code_placeholder') }}" class="rounded-xl border-base-300" />
                        <div class="md:col-span-2">
                            <x-mary-textarea label="{{ __('setup.wizard.school_address') }}" wire:model.live="schoolAddress" rows="3" class="rounded-xl border-base-300" />
                        </div>
                        <x-mary-input label="{{ __('setup.wizard.school_email') }}" type="email" wire:model.live="schoolEmail" placeholder="{{ __('setup.wizard.school_email_placeholder') }}" class="rounded-xl border-base-300" />
                        <x-mary-input label="{{ __('setup.wizard.school_phone') }}" wire:model.live="schoolPhone" placeholder="{{ __('setup.wizard.school_phone_placeholder') }}" class="rounded-xl border-base-300" />
                        <x-mary-input label="{{ __('setup.wizard.school_website') }}" type="url" wire:model.live="schoolWebsite" placeholder="{{ __('setup.wizard.school_website_placeholder') }}" class="rounded-xl border-base-300" />
                        <x-mary-input label="{{ __('setup.wizard.principal_name') }}" wire:model.live="principalName" placeholder="{{ __('setup.wizard.principal_name_placeholder') }}" class="rounded-xl border-base-300" />
                    </div>
                    
                    <div class="mt-12 flex justify-between">
                        <x-mary-button label="{{ __('setup.wizard.back') }}" wire:click="prevStep" class="btn-ghost rounded-xl font-bold uppercase tracking-widest" />
                        <x-mary-button label="{{ __('setup.wizard.next_step') }}" icon-right="o-arrow-right" class="btn-primary rounded-xl font-black uppercase tracking-widest px-8" wire:click="nextStep" />
                    </div>
                </div>
            @endif

            {{-- Step 3: Super Admin Account --}}
            @if($currentStep === 3)
                <div class="p-4 md:p-8">
                    <div class="flex items-center gap-4 mb-8">
                        <div class="size-12 rounded-2xl bg-base-200 flex items-center justify-center text-primary">
                            <x-mary-icon name="o-user-circle" class="size-6" />
                        </div>
                        <div>
                            <h2 class="text-2xl font-black tracking-tight">{{ __('setup.wizard.admin_account') }}</h2>
                            <p class="text-xs text-base-content/50 uppercase font-bold tracking-widest">{{ __('setup.wizard.admin_subtitle') }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-mary-input label="{{ __('setup.wizard.full_name') }}" wire:model.live="adminName" class="rounded-xl border-base-300" />
                        <x-mary-input label="{{ __('setup.wizard.email_address') }}" type="email" wire:model.live="adminEmail" class="rounded-xl border-base-300" />
                        
                        <div class="md:col-span-2 bg-primary/5 p-4 rounded-2xl border border-primary/10">
                            <div class="flex items-start gap-4">
                                <div class="shrink-0 size-10 rounded-xl bg-primary/20 text-primary flex items-center justify-center">
                                    <x-mary-icon name="o-finger-print" class="size-6" />
                                </div>
                                <div class="flex-1">
                                    <label class="text-xs font-black uppercase tracking-widest text-primary/60 block mb-1">{{ __('setup.wizard.username') }}</label>
                                    <div class="flex items-center gap-2">
                                        <span class="text-2xl font-black tracking-tight text-primary">{{ $adminUsername }}</span>
                                        <div class="badge badge-primary badge-outline font-bold uppercase text-[10px]">{{ __('setup.wizard.generated') }}</div>
                                    </div>
                                    <p class="text-[11px] text-base-content/50 mt-2 leading-relaxed">
                                        {{ __('setup.wizard.username_notice') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <x-mary-input label="{{ __('setup.wizard.password') }}" type="password" wire:model.live="adminPassword" class="rounded-xl border-base-300" />
                        <x-mary-input label="{{ __('setup.wizard.confirm_password') }}" type="password" wire:model.live="adminPassword_confirmation" class="rounded-xl border-base-300" />
                    </div>
                    
                    <div class="mt-12 flex justify-between">
                        <x-mary-button label="{{ __('setup.wizard.back') }}" wire:click="prevStep" class="btn-ghost rounded-xl font-bold uppercase tracking-widest" />
                        <x-mary-button label="{{ __('setup.wizard.next_step') }}" icon-right="o-arrow-right" class="btn-primary rounded-xl font-black uppercase tracking-widest px-8" wire:click="nextStep" />
                    </div>
                </div>
            @endif

            {{-- Step 4: Department --}}
            @if($currentStep === 4)
                <div class="p-4 md:p-8">
                    <div class="flex items-center gap-4 mb-8">
                        <div class="size-12 rounded-2xl bg-base-200 flex items-center justify-center text-primary">
                            <x-mary-icon name="o-rectangle-group" class="size-6" />
                        </div>
                        <div>
                            <h2 class="text-2xl font-black tracking-tight">{{ __('setup.wizard.department') }}</h2>
                            <p class="text-xs text-base-content/50 uppercase font-bold tracking-widest">{{ __('setup.wizard.department_subtitle') }}</p>
                        </div>
                    </div>

                    <p class="mb-8 text-sm text-base-content/60 leading-relaxed bg-base-200/50 p-4 rounded-2xl">
                        {{ __('setup.wizard.department_desc') }}
                    </p>
                    
                    <x-mary-input label="{{ __('setup.wizard.department_name') }}" wire:model.live="departmentName" placeholder="{{ __('setup.wizard.department_name_placeholder') }}" class="rounded-xl border-base-300" />

                    <div class="mt-4">
                        <x-mary-textarea 
                            label="{{ __('setup.wizard.department_description') }}" 
                            wire:model.live="departmentDescription" 
                            placeholder="{{ __('setup.wizard.department_description_placeholder') }}" 
                            class="rounded-xl border-base-300" 
                            rows="3" />
                    </div>
                    
                    <div class="mt-12 flex justify-between">
                        <x-mary-button label="{{ __('setup.wizard.back') }}" wire:click="prevStep" class="btn-ghost rounded-xl font-bold uppercase tracking-widest" />
                        <x-mary-button label="{{ __('setup.wizard.next_step') }}" icon-right="o-arrow-right" class="btn-primary rounded-xl font-black uppercase tracking-widest px-8" wire:click="nextStep" />
                    </div>
                </div>
            @endif

            {{-- Step 5: Internship Program --}}
            @if($currentStep === 5)
                <div class="p-4 md:p-8">
                    <div class="flex items-center gap-4 mb-8">
                        <div class="size-12 rounded-2xl bg-base-200 flex items-center justify-center text-primary">
                            <x-mary-icon name="o-briefcase" class="size-6" />
                        </div>
                        <div>
                            <h2 class="text-2xl font-black tracking-tight">{{ __('setup.wizard.internship') }}</h2>
                            <p class="text-xs text-base-content/50 uppercase font-bold tracking-widest">{{ __('setup.wizard.internship_subtitle') }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6">
                        <x-mary-input label="{{ __('setup.wizard.program_name') }}" wire:model.live="internshipName" placeholder="{{ __('setup.wizard.program_name_placeholder') }}" class="rounded-xl border-base-300" />
                        
                        <x-mary-textarea 
                            label="{{ __('setup.wizard.program_description') }}" 
                            wire:model.live="internshipDescription" 
                            placeholder="{{ __('setup.wizard.program_description_placeholder') }}" 
                            class="rounded-xl border-base-300" 
                            rows="2" />

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <x-mary-input label="{{ __('setup.wizard.start_date') }}" type="date" wire:model.live="startDate" class="rounded-xl border-base-300" />
                            <x-mary-input label="{{ __('setup.wizard.end_date') }}" type="date" wire:model.live="endDate" class="rounded-xl border-base-300" />
                        </div>
                    </div>
                    
                    <div class="mt-12 flex justify-between">
                        <x-mary-button label="{{ __('setup.wizard.back') }}" wire:click="prevStep" class="btn-ghost rounded-xl font-bold uppercase tracking-widest" />
                        <x-mary-button label="{{ __('setup.wizard.next_step') }}" icon-right="o-arrow-right" class="btn-primary rounded-xl font-black uppercase tracking-widest px-8" wire:click="nextStep" />
                    </div>
                </div>
            @endif

            {{-- Step 6: Finalize --}}
            @if($currentStep === 6)
                <div class="p-4 md:p-8">
                    <div class="flex items-center gap-4 mb-8">
                        <div class="size-12 rounded-2xl bg-base-200 flex items-center justify-center text-primary">
                            <x-mary-icon name="o-clipboard-document-check" class="size-6" />
                        </div>
                        <div>
                            <h2 class="text-2xl font-black tracking-tight">{{ __('setup.wizard.finalize') }}</h2>
                            <p class="text-xs text-base-content/50 uppercase font-bold tracking-widest">{{ __('setup.wizard.finalize_subtitle') }}</p>
                        </div>
                    </div>

                    <div class="space-y-4 mb-10">
                        <label class="flex items-center gap-4 p-5 bg-base-200/50 hover:bg-base-200 rounded-2xl cursor-pointer transition-colors border border-transparent hover:border-primary/20 group">
                            <input type="checkbox" wire:model.live="dataVerified" class="checkbox checkbox-primary rounded-lg" />
                            <span class="text-sm font-bold text-base-content/70 group-hover:text-base-content">{{ __('setup.wizard.data_verified') }}</span>
                        </label>
                        <label class="flex items-center gap-4 p-5 bg-base-200/50 hover:bg-base-200 rounded-2xl cursor-pointer transition-colors border border-transparent hover:border-primary/20 group">
                            <input type="checkbox" wire:model.live="securityAware" class="checkbox checkbox-primary rounded-lg" />
                            <span class="text-sm font-bold text-base-content/70 group-hover:text-base-content">{{ __('setup.wizard.security_aware') }}</span>
                        </label>
                    </div>

                    <div class="mt-12 flex justify-between">
                        <x-mary-button label="{{ __('setup.wizard.back') }}" wire:click="prevStep" class="btn-ghost rounded-xl font-bold uppercase tracking-widest" />
                        <x-mary-button label="{{ __('setup.wizard.finish_setup') }}" icon-right="o-check" class="btn-primary rounded-xl font-black uppercase tracking-widest px-8" wire:click="finish" spinner="finish" />
                    </div>
                </div>
            @endif

            {{-- Step 7: Complete --}}
            @if($currentStep === 7)
                <div class="p-4 md:p-12 text-center">
                    <div class="inline-flex items-center justify-center size-24 rounded-full bg-success/10 text-success mb-8">
                        <x-mary-icon name="o-check-badge" class="size-12" />
                    </div>
                    <h2 class="text-4xl font-black tracking-tighter mb-4">{{ __('setup.wizard.setup_complete') }}</h2>
                    <p class="text-base-content/60 mb-8 max-w-md mx-auto leading-relaxed font-medium">
                        {{ __('setup.wizard.ready_desc') }}
                    </p>

                    {{-- Admin Credentials Summary --}}
                    <div class="max-w-sm mx-auto bg-base-200/50 rounded-3xl p-6 mb-10 border border-success/20">
                        <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-success mb-4">{{ __('setup.wizard.admin_credentials') }}</h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-base-content/50">{{ __('setup.wizard.username') }}</span>
                                <span class="font-black text-primary">{{ $adminUsername }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-base-content/50">{{ __('setup.wizard.email') }}</span>
                                <span class="font-black">{{ $adminEmail }}</span>
                            </div>
                            <div class="divider my-1 opacity-10"></div>
                            <p class="text-[10px] text-base-content/40 leading-tight">
                                {{ __('setup.wizard.login_notice') }}
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-center">
                        <x-mary-button label="{{ __('setup.wizard.go_to_login') }}" icon-right="o-arrow-right" class="btn-success btn-wide rounded-2xl font-black uppercase tracking-widest text-white" wire:click="finishSession" />
                    </div>
                </div>
            @endif
        </x-mary-card>
    </div>
</div>
