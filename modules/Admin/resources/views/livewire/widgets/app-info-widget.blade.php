<div>
    <x-ui::card class="bg-transparent border-none shadow-none">
        <div class="flex flex-col gap-3">
            <div class="flex items-center gap-3">
                <div class="bg-primary/10 p-2 rounded-lg">
                    <x-ui::icon name="tabler.info-circle" class="w-5 h-5 text-primary" />
                </div>
                <div>
                    <h4 class="font-bold text-sm leading-none">{{ $appInfo['name'] ?? 'Internara' }}</h4>
                    <span class="text-[10px] opacity-50 uppercase tracking-widest font-black">{{ $appInfo['version'] ?? 'v0.0.0' }}</span>
                </div>
            </div>

            <div class="space-y-1">
                <div class="flex justify-between text-[11px] opacity-70">
                    <span>{{ __('admin::ui.dashboard.widget.license') }}</span>
                    <span>{{ $appInfo['license'] ?? '-' }}</span>
                </div>
            </div>

            <hr class="opacity-10">

            <div class="flex items-center justify-between">
                <div class="text-[10px] opacity-40">
                    &copy; {{ date('Y') }} {{ $appInfo['author']['name'] ?? 'Developer' }}
                </div>
                <div class="flex gap-2">
                    <a href="{{ $appInfo['author']['github'] ?? 'https://github.com/reasnov' }}" target="_blank" class="btn btn-ghost btn-xs btn-circle opacity-40 hover:opacity-100">
                        <x-ui::icon name="tabler.brand-github" class="w-3 h-3" />
                    </a>
                </div>
            </div>
        </div>
    </x-ui::card>
</div>