<div class="p-4 md:p-8 max-w-4xl mx-auto">
    {{-- Progress Header --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-3xl font-bold text-primary">{{ $appName }} @lang('setup.wizard.title')</h1>
            <span class="text-sm text-base-content/70">@lang('setup.wizard.step_of', ['current' => $currentStep])</span>
        </div>
        <progress class="progress progress-primary w-full" value="{{ $progress }}" max="100"></progress>
    </div>

    <x-mary-card shadow class="bg-base-100 border border-base-200">
        {{-- Step1: Welcome & System Requirements --}}
        @if($currentStep === 1)
            <div>
                <div class="text-center py-6">
                    <div class="flex justify-center mb-4">
                        <x-mary-icon name="o-rocket-launch" class="w-20 h-20 text-primary" />
                    </div>
                    <h2 class="text-2xl font-bold mb-2">@lang('setup.wizard.welcome')</h2>
                    <p class="text-base-content/70 max-w-md mx-auto">
                        @lang('setup.wizard.welcome_desc')
                    </p>
                </div>

                <div class="divider">@lang('setup.wizard.system_requirements')</div>

                <div class="space-y-3 mb-6">
                    @foreach($auditResults['checks'] as $check)
                        <div class="flex items-center gap-3 p-3 rounded-lg {{ $check['status'] === 'fail' ? 'bg-error/10' : ($check['status'] === 'warn' ? 'bg-warning/10' : 'bg-success/10') }}">
                            @if($check['status'] === 'pass')
                                <x-mary-icon name="o-check-circle" class="w-5 h-5 text-success flex-shrink-0" />
                            @elseif($check['status'] === 'fail')
                                <x-mary-icon name="o-x-circle" class="w-5 h-5 text-error flex-shrink-0" />
                            @else
                                <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5 text-warning flex-shrink-0" />
                            @endif
                            <div class="flex-1 min-w-0">
                                <span class="text-sm font-medium">{{ $check['name'] }}</span>
                                <p class="text-xs text-base-content/60 truncate">{{ $check['message'] }}</p>
                            </div>
                            <x-mary-badge
                                value="{{ $check['status'] === 'pass' ? 'OK' : ($check['status'] === 'fail' ? 'FAIL' : 'WARN') }}"
                                class="{{ $check['status'] === 'pass' ? 'badge-success' : ($check['status'] === 'fail' ? 'badge-error' : 'badge-warning') }} badge-sm"
                            />
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-between">
                    <div></div>
                    @if($auditPassed)
                        <x-mary-button label="@lang('setup.wizard.start_setup')" icon="o-play" class="btn-primary btn-lg" wire:click="nextStep" />
                    @else
                        <x-mary-button label="@lang('setup.wizard.recheck')" icon="o-arrow-path" class="btn-warning" wire:click="runAudit" spinner="runAudit" />
                    @endif
                </div>
            </div>
        @endif

        {{-- Step 2: School Information --}}
        @if($currentStep === 2)
            <div>
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                    <x-mary-icon name="o-academic-cap" class="w-6 h-6" />
                    @lang('setup.wizard.school_info')
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-mary-input label="@lang('setup.wizard.school_name')" wire:model="schoolName" placeholder="e.g. SMK Negeri 1 Jakarta" />
                    <x-mary-input label="@lang('setup.wizard.school_code')" wire:model="schoolCode" placeholder="NPSN / Unique ID" />
                    <div class="md:col-span-2">
                        <x-mary-textarea label="@lang('setup.wizard.school_address')" wire:model="schoolAddress" rows="3" />
                    </div>
                    <x-mary-input label="@lang('setup.wizard.school_email')" type="email" wire:model="schoolEmail" placeholder="admin@school.ac.id" />
                    <x-mary-input label="@lang('setup.wizard.school_phone')" wire:model="schoolPhone" placeholder="+62 21 12345678" />
                    <x-mary-input label="@lang('setup.wizard.principal_name')" wire:model="principalName" placeholder="Full name of principal" />
                </div>
                <div class="mt-8 flex justify-between">
                    <x-mary-button label="@lang('setup.wizard.back')" wire:click="prevStep" />
                    <x-mary-button label="@lang('setup.wizard.next_step')" class="btn-primary" wire:click="nextStep" />
                </div>
            </div>
        @endif

        {{-- Step 3: Super Admin Account --}}
        @if($currentStep === 3)
            <div>
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                    <x-mary-icon name="o-user-circle" class="w-6 h-6" />
                    @lang('setup.wizard.admin_account')
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-mary-input label="@lang('setup.wizard.full_name')" wire:model="adminName" />
                    <x-mary-input label="@lang('setup.wizard.email_address')" type="email" wire:model="adminEmail" />
                    <x-mary-input label="@lang('setup.wizard.username')" wire:model="adminUsername" />
                    <x-mary-input label="@lang('setup.wizard.password')" type="password" wire:model="adminPassword" />
                    <x-mary-input label="@lang('setup.wizard.confirm_password')" type="password" wire:model="adminPassword_confirmation" />
                </div>
                <div class="mt-8 flex justify-between">
                    <x-mary-button label="@lang('setup.wizard.back')" wire:click="prevStep" />
                    <x-mary-button label="@lang('setup.wizard.next_step')" class="btn-primary" wire:click="nextStep" />
                </div>
            </div>
        @endif

        {{-- Step 4: Department --}}
        @if($currentStep === 4)
            <div>
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                    <x-mary-icon name="o-rectangle-group" class="w-6 h-6" />
                    @lang('setup.wizard.department')
                </h2>
                <p class="mb-6 text-sm text-base-content/70">@lang('setup.wizard.department_desc')</p>
                <x-mary-input label="@lang('setup.wizard.department_name')" wire:model="departmentName" placeholder="e.g. Rekayasa Perangkat Lunak" />
                <div class="mt-8 flex justify-between">
                    <x-mary-button label="@lang('setup.wizard.back')" wire:click="prevStep" />
                    <x-mary-button label="@lang('setup.wizard.next_step')" class="btn-primary" wire:click="nextStep" />
                </div>
            </div>
        @endif

        {{-- Step 5: Internship Program --}}
        @if($currentStep === 5)
            <div>
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                    <x-mary-icon name="o-briefcase" class="w-6 h-6" />
                    @lang('setup.wizard.internship')
                </h2>
                <div class="grid grid-cols-1 gap-6">
                    <x-mary-input label="@lang('setup.wizard.program_name')" wire:model="internshipName" placeholder="e.g. PKL Semester Ganjil 2026" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-mary-input label="@lang('setup.wizard.start_date')" type="date" wire:model="startDate" />
                        <x-mary-input label="@lang('setup.wizard.end_date')" type="date" wire:model="endDate" />
                    </div>
                </div>
                <div class="mt-8 flex justify-between">
                    <x-mary-button label="@lang('setup.wizard.back')" wire:click="prevStep" />
                    <x-mary-button label="@lang('setup.wizard.next_step')" class="btn-primary" wire:click="nextStep" />
                </div>
            </div>
        @endif

        {{-- Step 6: Finalize --}}
        @if($currentStep === 6)
            <div>
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                    <x-mary-icon name="o-clipboard-document-check" class="w-6 h-6" />
                    @lang('setup.wizard.finalize', 'Finalize Setup')
                </h2>
                <div class="space-y-4 mb-6">
                    <label class="flex items-center gap-3 p-4 bg-base-200 rounded-lg cursor-pointer">
                        <input type="checkbox" wire:model="dataVerified" class="checkbox checkbox-primary" />
                        <span class="text-sm">@lang('setup.wizard.data_verified')</span>
                    </label>
                    <label class="flex items-center gap-3 p-4 bg-base-200 rounded-lg cursor-pointer">
                        <input type="checkbox" wire:model="securityAware" class="checkbox checkbox-primary" />
                        <span class="text-sm">@lang('setup.wizard.security_aware')</span>
                    </label>
                </div>
                <div class="mt-8 flex justify-between">
                    <x-mary-button label="@lang('setup.wizard.back')" wire:click="prevStep" />
                    <x-mary-button label="@lang('setup.wizard.finish_setup')" class="btn-primary" wire:click="finish" spinner="finish" />
                </div>
            </div>
        @endif

        {{-- Step 7: Complete --}}
        @if($currentStep === 7)
            <div class="text-center py-8">
                <div class="flex justify-center mb-6">
                    <div class="avatar placeholder">
                        <div class="bg-success text-success-content rounded-full w-24">
                            <x-mary-icon name="o-check-circle" class="w-16 h-16" />
                        </div>
                    </div>
                </div>
                <h2 class="text-2xl font-bold mb-4">@lang('setup.wizard.setup_complete')</h2>
                <p class="text-base-content/70 mb-8 max-w-md mx-auto">
                    @lang('setup.wizard.ready_desc')
                </p>
                <div class="flex justify-center gap-4">
                    <x-mary-button label="@lang('setup.wizard.go_to_login')" icon="o-arrow-right" class="btn-success btn-lg" link="{{ route('login') }}" />
                </div>
            </div>
        @endif
    </x-mary-card>

    <livewire:layout.app-signature />
</div>
