<div>
    <x-mary-header :title="__('sysadmin.clone_detection.title')" :subtitle="__('sysadmin.clone_detection.subtitle')" separator />

    <x-mary-card>
        @forelse($clones as $clone)
            <div class="py-3 border-b border-base-200 last:border-b-0">
                <div class="flex items-start gap-3">
                    <x-mary-icon name="o-exclamation-triangle" class="text-warning w-5 h-5 mt-0.5" />
                    <div>
                        <p class="text-sm">{{ $clone['reason'] ?? 'Potential clone detected' }}</p>
                        <p class="text-xs text-base-content/50">{{ $clone['email'] ?? 'Unknown' }}</p>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-8 opacity-60">
                <x-mary-icon name="o-check-circle" class="w-12 h-12 mx-auto mb-3 text-success" />
                <p>No suspicious accounts detected.</p>
            </div>
        @endforelse
    </x-mary-card>
</div>
