<div>
    <x-ui::components.page-header
        :title="__('sysadmin.clone_detection.title')"
        :description="__('sysadmin.clone_detection.subtitle')"
    />

    <x-ts-card shadowless>
        @forelse ($clones as $clone)
            <div class="border-base-200 border-b py-3 last:border-b-0">
                <div class="flex items-start gap-3">
                    <x-ts-icon name="exclamation-triangle" class="text-warning mt-0.5 h-5 w-5" />
                    <div>
                        <p class="text-sm">{{ $clone['reason'] ?? __('sysadmin.clone_detection.potential_clone') }}</p>
                        <p class="text-base-content/50 text-xs">{{ $clone['email'] ?? __('common.unknown') }}</p>
                    </div>
                </div>
            </div>
        @empty
            <div class="py-8 text-center opacity-60">
                <x-ts-icon name="check-circle" class="text-success mx-auto mb-3 h-12 w-12" />
                <p>{{ __('sysadmin.clone_detection.no_suspicious') }}</p>
            </div>
        @endforelse
    </x-ts-card>
</div>
