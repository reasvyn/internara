<div>
    <x-mary-header :title="__('guidance.title')" :subtitle="__('guidance.student_subtitle')" separator />

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($this->handbooks as $handbook)
            @php $entity = $handbook->asHandbook(); @endphp
            @php $lastAck = $this->acknowledgments[$handbook->id] ?? null; @endphp

            <x-mary-card :title="$entity->title()" separator class="shadow-sm">
                <div class="space-y-3">
                    @if($entity->description())
                        <p class="text-sm text-base-content/70">{{ $entity->description() }}</p>
                    @endif

                    <div class="flex items-center gap-2 text-xs text-base-content/50">
                        <x-mary-icon name="o-document-text" class="w-3.5 h-3.5" />
                        <span>v{{ $entity->version() }}</span>
                    </div>

                    <div class="flex items-center gap-2">
                        @if($entity->isNewerThan($lastAck))
                            <x-mary-button :label="__('guidance.acknowledge')" icon="o-check" class="btn-primary btn-sm" wire:click="acknowledge('{{ $handbook->id }}')" />
                        @else
                            <x-mary-badge :value="__('common.actions.done')" class="badge-success badge-sm" />
                        @endif

                        <x-mary-button :label="__('guidance.download')" icon="o-arrow-down-tray" class="btn-ghost btn-sm" wire:click="download('{{ $handbook->id }}')" />
                    </div>
                </div>
            </x-mary-card>
        @empty
            <div class="col-span-full">
                <x-mary-alert :title="__('guidance.no_handbooks')" icon="o-information-circle" class="bg-base-200" />
            </div>
        @endforelse
    </div>
</div>
