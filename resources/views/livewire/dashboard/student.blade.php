<div class="animate-in fade-in slide-in-from-bottom-8 duration-1000">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-black tracking-tightest text-base-content">{{ __('dashboard.title') ?? 'Dashboard' }}</h2>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-base-content/40 mt-2">Welcome back, {{ auth()->user()->name }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Left Column: Attendance & Stats --}}
        <div class="space-y-8">
            <livewire:student.attendance-widget />

            <x-mary-card shadow class="card-enterprise !bg-base-100 shadow-2xl shadow-base-content/5 border border-base-content/5 relative overflow-hidden">
                <div class="absolute top-0 right-0 size-32 bg-primary/5 rounded-full blur-2xl pointer-events-none"></div>
                <x-slot:title>
                    <span class="text-xl font-black tracking-tightest relative z-10">Internship Identity</span>
                </x-slot:title>

                @if($registration)
                    <div class="space-y-6 mt-4 relative z-10">
                        <div class="flex items-center gap-5">
                            <div class="size-14 rounded-[1.5rem] bg-base-200/50 flex items-center justify-center shadow-inner border border-base-content/5 p-2 shrink-0">
                                <img src="{{ $registration->placement->company->logo ?? asset('brand/logo.png') }}" class="size-full object-contain" alt="Company Logo" />
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[9px] font-black uppercase tracking-[0.3em] text-base-content/40 mb-1">Assigned Company</span>
                                <span class="text-sm font-black tracking-tight text-base-content">{{ $registration->placement->company->name }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 rounded-[1.5rem] bg-base-200/50 border border-base-content/5 transition-colors hover:bg-base-200">
                                <span class="text-[8px] font-black uppercase tracking-widest text-base-content/40 block mb-1">Position</span>
                                <span class="text-xs font-bold">{{ $registration->placement->name }}</span>
                            </div>
                            <div class="p-4 rounded-[1.5rem] bg-base-200/50 border border-base-content/5 transition-colors hover:bg-base-200">
                                <span class="text-[8px] font-black uppercase tracking-widest text-base-content/40 block mb-1">Batch</span>
                                <span class="text-xs font-bold">{{ $registration->internship->name }}</span>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-base-content/5">
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-[9px] font-black uppercase tracking-[0.2em] text-base-content/40">Journal Verification</span>
                                <x-mary-badge value="{{ $verifiedJournals }}/{{ $totalJournals }}" class="badge-neutral font-black text-[10px]" />
                            </div>
                            <progress class="progress progress-primary h-2 w-full rounded-full" value="{{ $totalJournals > 0 ? ($verifiedJournals / $totalJournals) * 100 : 0 }}" max="100"></progress>
                            <p class="text-[10px] text-base-content/40 mt-3 font-medium uppercase tracking-widest leading-relaxed">Keep your journals updated for timely verification.</p>
                        </div>
                    </div>
                @else
                    <div class="text-center py-8 relative z-10">
                        <div class="size-16 rounded-[2rem] bg-base-200/50 mx-auto flex items-center justify-center mb-4 text-base-content/20 shadow-inner">
                            <x-mary-icon name="o-shield-exclamation" class="size-8" />
                        </div>
                        <h4 class="font-black text-sm uppercase tracking-widest">Assignment Pending</h4>
                        <p class="text-[10px] text-base-content/40 mt-2 max-w-[200px] mx-auto font-medium uppercase tracking-[0.1em] leading-relaxed">Please contact your department coordinator for placement details.</p>
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
                    class="btn-primary h-24 rounded-[2rem] font-black uppercase tracking-[0.1em] text-xs shadow-2xl shadow-primary/20 transition-transform duration-300 hover:scale-[1.02]" 
                    link="{{ route('student.journals') }}" 
                    wire:navigate
                />
                
                <x-mary-button 
                    label="Request Absence" 
                    icon="o-document-plus" 
                    class="bg-base-100 hover:bg-base-200 border-none h-24 rounded-[2rem] font-black uppercase tracking-[0.1em] text-xs shadow-2xl shadow-base-content/5 transition-transform duration-300 hover:scale-[1.02]" 
                />
            </div>

            <x-mary-card shadow class="card-enterprise !bg-base-100 shadow-2xl shadow-base-content/5 border border-base-content/5">
                <x-slot:title>
                    <span class="text-xl font-black tracking-tightest">Timeline Activity</span>
                </x-slot:title>
                <div class="flex flex-col items-center justify-center py-16 opacity-20">
                    <div class="size-20 rounded-[2.5rem] bg-base-200 flex items-center justify-center mb-4 shadow-inner">
                        <x-mary-icon name="o-queue-list" class="size-10" />
                    </div>
                    <span class="font-black uppercase tracking-[0.3em] text-[9px]">Activity stream coming soon</span>
                </div>
            </x-mary-card>
        </div>
    </div>
</div>
