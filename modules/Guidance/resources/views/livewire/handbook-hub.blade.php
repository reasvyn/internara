<div id="handbook-hub" class="scroll-mt-6">
    <x-ui::card title="{{ __('guidance::ui.hub_title') }}" shadow separator>
        <div class="space-y-4">
            @foreach($handbooks as $handbook)
                <div class="flex items-center justify-between p-4 bg-base-200 rounded-xl hover:bg-base-300 transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="bg-primary/10 p-3 rounded-lg">
                            <x-ui::icon name="tabler.book" class="w-6 h-6 text-primary" aria-hidden="true" />
                        </div>
                        <div>
                            <div class="font-bold flex items-center gap-2">
                                {{ $handbook->title }}
                                @if($handbook->is_mandatory)
                                    <span class="badge badge-error badge-xs" aria-label="{{ __('guidance::ui.mandatory') }}">
                                        {{ __('guidance::ui.mandatory') }}
                                    </span>
                                @endif
                            </div>
                            <div class="text-xs opacity-60">{{ __('guidance::ui.version', ['v' => $handbook->version]) }}</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        @php
                            $isAcknowledged = app(\Modules\Guidance\Services\Contracts\HandbookService::class)->hasAcknowledged(auth()->id() ?: '', $handbook->id);
                        @endphp

                        @if($isAcknowledged)
                            <div class="tooltip tooltip-left" data-tip="{{ __('guidance::ui.already_read') }}">
                                <x-ui::icon name="tabler.circle-check-filled" class="w-6 h-6 text-success" aria-label="{{ __('guidance::ui.already_read') }}" />
                            </div>
                        @else
                            <x-ui::button 
                                label="{{ __('guidance::ui.read_and_agree') }}" 
                                class="btn-sm btn-ghost" 
                                wire:click="acknowledge('{{ $handbook->id }}')" 
                                wire:loading.attr="disabled"
                                aria-label="{{ __('guidance::ui.read_and_agree') }}: {{ $handbook->title }}"
                            />
                        @endif

                        <x-ui::button 
                            icon="tabler.download" 
                            class="btn-sm btn-circle btn-primary" 
                            link="{{ route('guidance.download', $handbook->id) }}" 
                            title="{{ __('guidance::ui.download_pdf') }}"
                            aria-label="{{ __('guidance::ui.download_pdf') }}: {{ $handbook->title }}"
                        />
                    </div>
                </div>
            @endforeach
        </div>
    </x-ui::card>
</div>
