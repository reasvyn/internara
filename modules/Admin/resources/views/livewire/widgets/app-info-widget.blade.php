<div>
    <x-ui::card class="bg-base-100/30 border-none shadow-none hover:bg-base-100/50 transition-colors duration-300">
        <div class="flex flex-col gap-4 p-1">
            <div class="flex items-center gap-3">
                <div class="bg-primary/10 p-2.5 rounded-2xl">
                    <x-ui::icon name="tabler.topology-star-3" class="w-5 h-5 text-primary" />
                </div>
                <div class="flex flex-col">
                    <h4 class="font-black text-sm tracking-tight leading-none mb-1 text-base-content/90">{{ $appInfo['name'] ?? 'Internara' }}</h4>
                <div class="flex items-center gap-1.5">
                         <span class="px-1.5 py-0.5 bg-base-300/50 rounded text-[8px] font-black uppercase tracking-widest opacity-60">
                             {{ $appInfo['version'] ?? 'v0.0.0' }}
                         </span>
                         <span class="text-[9px] opacity-40 font-medium">{{ __('admin::ui.dashboard.widget.enterprise_core') }}</span>
                     </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div class="bg-base-200/40 p-2 rounded-xl border border-base-content/5">
                     <span class="block text-[8px] uppercase opacity-40 font-bold mb-0.5">{{ __('admin::ui.dashboard.widget.license') }}</span>
                     <span class="block text-[10px] font-bold text-base-content/80">{{ $appInfo['license'] ?? __('admin::ui.dashboard.widget.proprietary') }}</span>
                 </div>
                 <div class="bg-base-200/40 p-2 rounded-xl border border-base-content/5">
                     <span class="block text-[8px] uppercase opacity-40 font-bold mb-0.5">{{ __('admin::ui.dashboard.widget.stability') }}</span>
                     <span class="block text-[10px] font-bold text-success">{{ __('admin::ui.dashboard.widget.stability_optimal') }}</span>
                 </div>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-base-content/5">
                <div class="text-[9px] opacity-30 font-medium italic">
                     &copy; {{ date('Y') }} {{ $appInfo['author']['name'] ?? __('admin::ui.dashboard.widget.author_name') }}
                 </div>
                <div class="flex gap-1">
                    <a href="{{ $appInfo['author']['github'] ?? 'https://github.com/reasvyn' }}" target="_blank" class="p-1 hover:bg-primary/10 rounded-lg transition-colors group">
                        <x-ui::icon name="tabler.brand-github" class="w-3.5 h-3.5 opacity-30 group-hover:opacity-100 transition-opacity" />
                    </a>
                </div>
            </div>
        </div>
    </x-ui::card>
</div>