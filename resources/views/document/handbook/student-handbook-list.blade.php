<div>
    <x-mary-header :title="__('handbook.title')" :subtitle="__('handbook.student_subtitle')" separator />

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($this->handbooks as $handbook)
            @php $entity = $handbook->asHandbook(); @endphp
            @php $lastAck = $this->acknowledgments[$handbook->id] ?? null; @endphp

            <x-ts-card shadowless :header="$entity->title()" class="shadow-sm">
                <div class="space-y-3">
                    @if ($entity->description())
                        <p class="text-base-content/70 text-sm">{{ $entity->description() }}</p>
                    @endif

                    <div class="text-base-content/50 flex items-center gap-2 text-xs">
                        <x-ts-icon name="document-text" class="h-3.5 w-3.5" />
                        <span>v{{ $entity->version() }}</span>
                    </div>

                    <div class="flex items-center gap-2">
                        @if ($entity->isNewerThan($lastAck))
                            <x-ts-button
                                :text="__('handbook.acknowledge')"
                                icon="check"
                                color="primary"
                                sm
                                wire:click="acknowledge('{{ $handbook->id }}')"
                            />
                        @else
                            <x-ts-badge :text="__('common.actions.done')" class="badge-success badge-sm" />
                        @endif

                        <x-ts-button
                            :text="__('handbook.download')"
                            icon="arrow-down-tray"
                            color="white"
                            sm
                            wire:click="download('{{ $handbook->id }}')"
                        />
                    </div>
                </div>

        @empty
            <div class="col-span-full">
                <x-mary-alert :title="__('handbook.no_handbooks')" icon="information-circle" class="bg-base-200" />
            </div>
        @endforelse
    </div>
</div>
