<div id="handbook-hub" class="scroll-mt-6">
    <x-ui::card :title="__('guidance::ui.hub_title')" shadow separator>
        <div class="space-y-4">
            @foreach($this->handbooks as $handbook)
                <div class="flex items-center justify-between p-4 bg-base-200 rounded-xl hover:bg-base-300 transition-all duration-300">
                    <div class="flex items-center gap-4">
                        <div class="bg-primary/10 p-3 rounded-lg">
                            <x-ui::icon name="tabler.book" class="size-6 text-primary" aria-hidden="true" />
                        </div>
                        <div>
                            <div class="font-bold flex items-center gap-2 leading-tight">
                                {{ $handbook->title }}
                                @if($handbook->is_mandatory)
                                    <x-ui::badge :value="__('guidance::ui.mandatory')" variant="primary" class="badge-xs" />
                                @endif
                            </div>
                            <div class="text-[10px] uppercase tracking-wider font-semibold opacity-60 mt-0.5">
                                {{ __('guidance::ui.version', ['v' => $handbook->version]) }}
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        @if($handbook->is_acknowledged)
                            <div class="tooltip tooltip-left" data-tip="{{ __('guidance::ui.already_read') }}">
                                <x-ui::icon name="tabler.circle-check-filled" class="size-6 text-success" aria-label="{{ __('guidance::ui.already_read') }}" />
                            </div>
                        @else
                            <x-ui::button 
                                :label="__('guidance::ui.read_and_agree')" 
                                variant="tertiary"
                                class="btn-sm" 
                                wire:click="acknowledge('{{ $handbook->id }}')" 
                                wire:loading.attr="disabled"
                                aria-label="{{ __('guidance::ui.read_and_agree') }}: {{ $handbook->title }}"
                            />
                        @endif

                        <x-ui::button 
                            icon="tabler.download" 
                            variant="primary"
                            class="btn-sm btn-circle" 
                            link="{{ route('guidance.download', $handbook->id) }}" 
                            tooltip="{{ __('guidance::ui.download_pdf') }}"
                            aria-label="{{ __('guidance::ui.download_pdf') }}: {{ $handbook->title }}"
                        />
                    </div>
                </div>
            @endforeach
        </div>
    </x-ui::card>
</div>
