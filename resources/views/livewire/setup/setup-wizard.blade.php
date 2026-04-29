<div class="p-4 md:p-8 max-w-4xl mx-auto">
    {{-- Progress Header --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-3xl font-bold text-primary">{{ $appName }} Setup</h1>
            <span class="text-sm text-base-content/70">Step {{ $currentStep }} of 6</span>
        </div>
        <progress class="progress progress-primary w-full" value="{{ ($currentStep / 6) * 100 }}" max="100"></progress>
    </div>

    <x-mary-card shadow class="bg-base-100 border border-base-200">
        {{-- Step 1: Welcome --}}
        @if($currentStep === 1)
            <div class="text-center py-8">
                <div class="flex justify-center mb-6">
                    <x-mary-icon name="o-rocket-launch" class="w-24 h-24 text-primary animate-bounce" />
                </div>
                <h2 class="text-2xl font-bold mb-4">Welcome to Internara</h2>
                <p class="text-base-content/70 mb-8 max-w-md mx-auto">
                    We will help you set up your professional internship management system in just a few steps.
                </p>
                <div class="flex justify-center">
                    <x-mary-button label="Start Setup" icon="o-play" class="btn-primary btn-lg" wire:click="nextStep" />
                </div>
            </div>
        @endif

        {{-- Step 2: School Information --}}
        @if($currentStep === 2)
            <div>
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                    <x-mary-icon name="o-academic-cap" class="w-6 h-6" />
                    School Information
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-mary-input label="School/Institution Name" wire:model="schoolName" placeholder="e.g. SMK Negeri 1 Jakarta" />
                    <x-mary-input label="Institutional Code" wire:model="schoolCode" placeholder="NPSN / Unique ID" />
                    <div class="md:col-span-2">
                        <x-mary-textarea label="Full Address" wire:model="schoolAddress" rows="3" />
                    </div>
                    <x-mary-input label="Principal Name" wire:model="principalName" />
                </div>
                <div class="mt-8 flex justify-between">
                    <x-mary-button label="Back" wire:click="prevStep" />
                    <x-mary-button label="Next Step" class="btn-primary" wire:click="nextStep" />
                </div>
            </div>
        @endif

        {{-- Step 3: Department --}}
        @if($currentStep === 3)
            <div>
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                    <x-mary-icon name="o-rectangle-group" class="w-6 h-6" />
                    Initial Department
                </h2>
                <p class="mb-6 text-sm text-base-content/70">Create your first department (Jurusan). You can add more later.</p>
                <x-mary-input label="Department Name" wire:model="departmentName" placeholder="e.g. Rekayasa Perangkat Lunak" />
                <div class="mt-8 flex justify-between">
                    <x-mary-button label="Back" wire:click="prevStep" />
                    <x-mary-button label="Next Step" class="btn-primary" wire:click="nextStep" />
                </div>
            </div>
        @endif

        {{-- Step 4: Internship Program --}}
        @if($currentStep === 4)
            <div>
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                    <x-mary-icon name="o-briefcase" class="w-6 h-6" />
                    Internship Batch
                </h2>
                <div class="grid grid-cols-1 gap-6">
                    <x-mary-input label="Program Name" wire:model="internshipName" placeholder="e.g. PKL Semester Ganjil 2026" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-mary-datepicker label="Start Date" wire:model="startDate" icon="o-calendar" />
                        <x-mary-datepicker label="End Date" wire:model="endDate" icon="o-calendar" />
                    </div>
                </div>
                <div class="mt-8 flex justify-between">
                    <x-mary-button label="Back" wire:click="prevStep" />
                    <x-mary-button label="Next Step" class="btn-primary" wire:click="nextStep" />
                </div>
            </div>
        @endif

        {{-- Step 5: Super Admin Account --}}
        @if($currentStep === 5)
            <div>
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                    <x-mary-icon name="o-user-circle" class="w-6 h-6" />
                    First Administrator
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-mary-input label="Full Name" wire:model="adminName" />
                    <x-mary-input label="Email Address" type="email" wire:model="adminEmail" />
                    <x-mary-input label="Username" wire:model="adminUsername" />
                    <x-mary-input label="Password" type="password" wire:model="adminPassword" />
                    <x-mary-input label="Confirm Password" type="password" wire:model="adminPasswordConfirmation" />
                </div>
                <div class="mt-8 flex justify-between">
                    <x-mary-button label="Back" wire:click="prevStep" />
                    <x-mary-button label="Finish Setup" class="btn-primary" wire:click="finish" spinner="finish" />
                </div>
            </div>
        @endif

        {{-- Step 6: Complete --}}
        @if($currentStep === 6)
            <div class="text-center py-8">
                <div class="flex justify-center mb-6">
                    <div class="avatar placeholder animate-pulse">
                        <div class="bg-success text-success-content rounded-full w-24">
                            <x-mary-icon name="o-check-circle" class="w-16 h-16" />
                        </div>
                    </div>
                </div>
                <h2 class="text-2xl font-bold mb-4">Setup Complete!</h2>
                <p class="text-base-content/70 mb-8 max-w-md mx-auto">
                    Internara is ready to manage your internship programs. You can now login with your administrator account.
                </p>
                <div class="flex justify-center">
                    <x-mary-button label="Go to Login" icon="o-arrow-right" class="btn-success btn-lg" link="{{ route('login') }}" />
                </div>
            </div>
        @endif
    </x-mary-card>

    <div class="mt-8 text-center text-xs text-base-content/50">
        &copy; {{ date('Y') }} {{ $appName }} v{{ $appVersion }} • Built with 3S Standards
    </div>
</div>
