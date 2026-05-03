<x-layouts.base :title="__('Unauthorized Access')">
    <div class="min-h-screen bg-base-200 flex items-center justify-center p-6">
        <div class="max-w-md w-full text-center">
            <div class="mb-10 relative inline-block">
                <div class="size-32 rounded-[2.5rem] bg-error/10 flex items-center justify-center text-error animate-pulse">
                    <x-mary-icon name="o-shield-exclamation" class="size-20" />
                </div>
                <div class="absolute -top-4 -right-4 size-12 rounded-2xl bg-base-100 shadow-xl flex items-center justify-center text-error font-black border border-error/20">
                    403
                </div>
            </div>
            
            <h1 class="text-4xl font-black tracking-tighter text-base-content mb-3 uppercase">Security Access Denied</h1>
            <p class="text-sm font-medium text-base-content/50 leading-relaxed mb-10 px-4">
                {{ $exception->getMessage() ?: __('Your identity profile does not have the required clearance level to access this encrypted node.') }}
            </p>
            
            <div class="grid grid-cols-2 gap-4">
                <x-mary-button 
                    label="Retreat" 
                    icon="o-arrow-left" 
                    link="{{ url()->previous() }}" 
                    class="bg-base-100 hover:bg-base-300 border-none rounded-2xl font-black uppercase tracking-widest text-[10px] shadow-lg shadow-base-content/5" 
                />
                
                <x-mary-button 
                    label="Re-Verify" 
                    icon="o-identification" 
                    link="{{ route('login') }}" 
                    class="btn-primary rounded-2xl font-black uppercase tracking-widest text-[10px] shadow-xl shadow-primary/20" 
                />
            </div>

            <div class="mt-16 flex items-center justify-center gap-2 opacity-20">
                <x-mary-icon name="o-shield-check" class="size-4" />
                <span class="text-[9px] font-black uppercase tracking-[0.3em]">{{ config('app.name') }} S1 Protocol Active</span>
            </div>
        </div>
    </div>
</x-layouts.base>
