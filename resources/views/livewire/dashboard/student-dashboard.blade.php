<div class="p-8">
    <x-mary-header title="Student Dashboard" subtitle="Welcome back, {{ auth()->user()->name }}" separator />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Left Column: Attendance & Stats --}}
        <div class="space-y-8">
            <livewire:student.attendance-widget />

            <x-mary-card title="Internship Info" shadow class="bg-base-200/50">
                @if($registration)
                    <div class="space-y-4">
                        <div>
                            <div class="text-xs opacity-50 uppercase font-bold">Company</div>
                            <div class="font-medium">{{ $registration->placement->company->name }}</div>
                        </div>
                        <div>
                            <div class="text-xs opacity-50 uppercase font-bold">Position</div>
                            <div class="font-medium">{{ $registration->placement->name }}</div>
                        </div>
                        <div>
                            <div class="text-xs opacity-50 uppercase font-bold">Batch</div>
                            <div class="font-medium">{{ $registration->internship->name }}</div>
                        </div>
                        <div class="pt-4 border-t border-base-300">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs font-bold opacity-50 uppercase">Journal Progress</span>
                                <span class="text-xs font-bold">{{ $verifiedJournals }}/{{ $totalJournals }} Verified</span>
                            </div>
                            <progress class="progress progress-primary w-full" value="{{ $totalJournals > 0 ? ($verifiedJournals / $totalJournals) * 100 : 0 }}" max="100"></progress>
                        </div>
                    </div>
                @else
                    <div class="text-center py-4">
                        <x-mary-icon name="o-information-circle" class="w-12 h-12 opacity-20 mb-2" />
                        <p class="text-sm opacity-50">No active internship found.</p>
                    </div>
                @endif
            </x-mary-card>
        </div>

        {{-- Right Column: Quick Links & Recent Activity --}}
        <div class="lg:col-span-2 space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-button label="Daily Journal" icon="o-pencil-square" class="btn-primary h-24 text-lg" link="{{ route('student.journals') }}" />
                <x-mary-button label="Absence Request" icon="o-document-plus" class="btn-outline h-24 text-lg" />
            </div>

            <x-mary-card title="Recent Activity" shadow>
                {{-- This would be a livewire list of recent logs/journals --}}
                <div class="text-center py-8 opacity-30">
                    <x-mary-icon name="o-queue-list" class="w-16 h-16 mx-auto mb-2" />
                    <p>Activity stream coming soon</p>
                </div>
            </x-mary-card>
        </div>
    </div>
</div>
