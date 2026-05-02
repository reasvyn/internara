<div class="p-8">
    <x-mary-header title="Dashboard" subtitle="Welcome back, {{ auth()->user()->name }}" separator progress-indicator />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Left Column: Attendance & Stats --}}
        <div class="space-y-8">
            <livewire:student.attendance-widget />

            <x-mary-card title="Internship Identity" shadow class="bg-base-200/50 rounded-[2.5rem] border-none">
                @if($registration)
                    <div class="space-y-6">
                        <div class="flex items-center gap-4">
                            <div class="size-12 rounded-2xl bg-white flex items-center justify-center shadow-sm border border-base-200/50 p-2">
                                <img src="{{ $registration->placement->company->logo ?? asset('brand/logo.png') }}" class="size-full object-contain" alt="Company Logo" />
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-base-content/30 leading-none mb-1">Assigned Company</span>
                                <span class="text-sm font-black tracking-tight text-base-content">{{ $registration->placement->company->name }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 rounded-2xl bg-base-100/50 border border-base-content/5">
                                <span class="text-[9px] font-black uppercase tracking-widest text-base-content/30 block mb-1">Position</span>
                                <span class="text-xs font-bold">{{ $registration->placement->name }}</span>
                            </div>
                            <div class="p-4 rounded-2xl bg-base-100/50 border border-base-content/5">
                                <span class="text-[9px] font-black uppercase tracking-widest text-base-content/30 block mb-1">Batch</span>
                                <span class="text-xs font-bold">{{ $registration->internship->name }}</span>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-base-content/5">
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-[10px] font-black uppercase tracking-[0.15em] text-base-content/30">Journal Verification</span>
                                <x-mary-badge value="{{ $verifiedJournals }}/{{ $totalJournals }}" class="badge-neutral font-black text-[10px]" />
                            </div>
                            <progress class="progress progress-primary h-2 w-full rounded-full" value="{{ $totalJournals > 0 ? ($verifiedJournals / $totalJournals) * 100 : 0 }}" max="100"></progress>
                            <p class="text-[10px] text-base-content/40 mt-3 italic font-medium">Keep your journals updated for timely verification.</p>
                        </div>
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="size-16 rounded-3xl bg-base-100 mx-auto flex items-center justify-center mb-4 text-base-content/10">
                            <x-mary-icon name="o-shield-exclamation" class="size-10" />
                        </div>
                        <h4 class="font-black text-sm uppercase tracking-tight">Assignment Pending</h4>
                        <p class="text-xs text-base-content/40 mt-1 max-w-[200px] mx-auto">Please contact your department coordinator for placement details.</p>
                    </div>
                @endif
            </x-mary-card>
        </div>

        {{-- Right Column: Quick Links & Recent Activity --}}
        <div class="lg:col-span-2 space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-mary-button 
                    label="Write Daily Journal" 
                    icon="o-pencil-square" 
                    class="btn-primary h-28 rounded-[2.5rem] font-black uppercase tracking-widest text-lg shadow-xl shadow-primary/20 transition-all hover:scale-[1.02]" 
                    link="{{ route('student.journals') }}" 
                />
                
                <x-mary-button 
                    label="Request Absence" 
                    icon="o-document-plus" 
                    class="bg-base-100 hover:bg-base-200 border-none h-28 rounded-[2.5rem] font-black uppercase tracking-widest text-lg shadow-xl shadow-base-content/5 transition-all hover:scale-[1.02]" 
                />
            </div>

            <x-mary-card title="Timeline Activity" shadow class="card-enterprise">
                <div class="flex flex-col items-center justify-center py-16 opacity-20">
                    <div class="size-20 rounded-[2rem] bg-base-200 flex items-center justify-center mb-4">
                        <x-mary-icon name="o-queue-list" class="size-10" />
                    </div>
                    <span class="font-black uppercase tracking-[0.2em] text-[10px]">Activity stream coming soon</span>
                </div>
            </x-mary-card>
        </div>
    </div>
</div>
