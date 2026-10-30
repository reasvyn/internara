<div>
    <x-ui::components.page-header
        :title="__('handbook.student_page_title')"
        :description="__('handbook.student_page_description')"
    />

    {{-- Reading Compliance Progress Metric --}}
    <x-ts-card shadowless class="border-base-content/10 bg-base-100 mb-6 rounded-lg border">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3.5">
                <div class="bg-primary/10 text-primary flex size-11 shrink-0 items-center justify-center rounded-md">
                    <x-ts-icon name="book-open" class="size-6" />
                </div>
                <div>
                    <h2 class="text-base-content text-base font-semibold tracking-tight">
                        {{ __('handbook.progress_title') }}
                    </h2>
                    <p class="text-base-content/70 text-xs">{{ __('handbook.progress_subtitle') }}</p>
                </div>
            </div>

            <div class="flex min-w-[220px] flex-col gap-1.5 sm:items-end">
                <div class="flex w-full items-center justify-between gap-2 sm:justify-end">
                    <span class="text-base-content/60 text-xs font-medium">
                        {{ $this->stats['read'] }} / {{ $this->stats['total'] }} {{ __('handbook.filter_completed') }}
                    </span>
                    @if ($this->stats['pending'] === 0)
                        <x-ts-badge :text="__('handbook.all_completed')" color="success" sm />
                    @else
                        <x-ts-badge
                            :text="__('handbook.pending_alert', ['count' => $this->stats['pending']])"
                            color="warning"
                            sm
                        />
                    @endif
                </div>
                <div class="bg-base-200 h-2 w-full overflow-hidden rounded-full">
                    <div
                        class="bg-primary h-full rounded-full transition-all duration-300"
                        style="width: {{ $this->stats['percent'] }}%"
                    ></div>
                </div>
            </div>
        </div>
    </x-ts-card>

    {{-- Interactive Controls: Filter Chips & Search --}}
    <div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
            <button
                type="button"
                wire:click="setFilter('all')"
                @class([
                    'cursor-pointer px-3 py-1.5 text-xs font-semibold rounded-md transition-colors',
                    'bg-primary text-primary-content shadow-2xs' => $filter === 'all',
                    'bg-base-200/80 text-base-content/70 hover:bg-base-200 hover:text-base-content' => $filter !== 'all',
                ])
            >
                {{ __('handbook.filter_all') }} ({{ $this->stats['total'] }})
            </button>
            <button
                type="button"
                wire:click="setFilter('pending')"
                @class([
                    'cursor-pointer px-3 py-1.5 text-xs font-semibold rounded-md transition-colors flex items-center gap-1.5',
                    'bg-primary text-primary-content shadow-2xs' => $filter === 'pending',
                    'bg-base-200/80 text-base-content/70 hover:bg-base-200 hover:text-base-content' => $filter !== 'pending',
                ])
            >
                <span>{{ __('handbook.filter_pending') }}</span>
                @if ($this->stats['pending'] > 0)
                    <span class="bg-warning size-1.5 rounded-full"></span>
                @endif
                <span class="text-xs opacity-80">({{ $this->stats['pending'] }})</span>
            </button>
            <button
                type="button"
                wire:click="setFilter('completed')"
                @class([
                    'cursor-pointer px-3 py-1.5 text-xs font-semibold rounded-md transition-colors',
                    'bg-primary text-primary-content shadow-2xs' => $filter === 'completed',
                    'bg-base-200/80 text-base-content/70 hover:bg-base-200 hover:text-base-content' => $filter !== 'completed',
                ])
            >
                {{ __('handbook.filter_completed') }} ({{ $this->stats['read'] }})
            </button>
        </div>

        <div class="w-full sm:w-72">
            <x-ts-input
                wire:model.live.debounce.300ms="search"
                :placeholder="__('handbook.search_placeholder')"
                icon="magnifying-glass"
                clearable
                sm
            />
        </div>
    </div>

    {{-- Handbook Cards Grid --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($this->handbooks as $handbook)
            @php
                $entity = $handbook->asHandbook();
                $lastAck = $this->acknowledgments[$handbook->id] ?? null;
                $isPending = $entity->isNewerThan($lastAck);
                $media = $handbook->relationLoaded('media') ? $handbook->getFirstMedia('handbook_file') : null;
            @endphp

            <div class="border-base-content/10 bg-base-100 hover:border-base-content/20 flex flex-col justify-between rounded-lg border p-5 shadow-2xs transition-all duration-200 hover:shadow-xs">
                <div>
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1.5">
                            <span class="badge badge-outline badge-sm text-base-content/60 font-mono text-xs">v{{ $entity->version() }}.0</span>
                        </div>

                        @if ($isPending)
                            <x-ts-badge :text="__('handbook.status_pending')" color="warning" sm />
                        @else
                            <x-ts-badge :text="__('handbook.status_completed')" color="success" sm />
                        @endif
                    </div>

                    <h3 class="text-base-content mt-3 line-clamp-1 text-base font-semibold tracking-tight">
                        {{ $entity->title() }}
                    </h3>

                    @if ($entity->description())
                        <p class="text-base-content/70 mt-1.5 line-clamp-2 text-xs leading-relaxed">
                            {{ $entity->description() }}
                        </p>
                    @endif

                    <div class="text-base-content/60 mt-4 flex flex-wrap items-center gap-3 text-xs">
                        <span class="inline-flex items-center gap-1">
                            <x-ts-icon name="document-text" class="size-3.5" />
                            <span>PDF</span>
                        </span>

                        @if ($media)
                            <span class="inline-flex items-center gap-1">
                                <x-ts-icon name="paper-clip" class="size-3.5" />
                                <span>{{ $media->human_readable_size }}</span>
                            </span>
                        @endif

                        @if (! $isPending && $lastAck?->created_at)
                            <span class="text-success inline-flex items-center gap-1 font-medium">
                                <x-ts-icon name="check" class="size-3.5" />
                                <span>{{ $lastAck->created_at->translatedFormat('d M Y') }}</span>
                            </span>
                        @endif
                    </div>
                </div>

                <div class="border-base-content/10 mt-5 flex items-center justify-between gap-2 border-t pt-3.5">
                    <x-ts-button
                        :title="__('handbook.download')"
                        icon="arrow-down-tray"
                        color="secondary"
                        outline
                        sm
                        wire:click="download('{{ $handbook->id }}')"
                    />

                    @if ($isPending)
                        <x-ts-button
                            :text="__('handbook.read_and_acknowledge')"
                            icon="book-open"
                            color="primary"
                            sm
                            wire:click="openReader('{{ $handbook->id }}')"
                        />
                    @else
                        <x-ts-button
                            :text="__('handbook.read_again')"
                            icon="eye"
                            color="secondary"
                            outline
                            sm
                            wire:click="openReader('{{ $handbook->id }}')"
                        />
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full">
                @if ($search !== '' || $filter !== 'all')
                    <div class="border-base-content/10 bg-base-100 flex flex-col items-center justify-center rounded-lg border p-8 text-center">
                        <x-ui::widgets.empty-state
                            icon="document-magnifying-glass"
                            :title="__('handbook.no_results_search')"
                        />
                        <div class="mt-4">
                            <x-ts-button
                                :text="__('handbook.reset_filter')"
                                icon="arrow-path"
                                color="secondary"
                                outline
                                sm
                                wire:click="resetFilters"
                            />
                        </div>
                    </div>
                @else
                    <div class="border-base-content/10 bg-base-100 flex flex-col items-center justify-center rounded-lg border p-8 text-center">
                        <x-ui::widgets.empty-state icon="book-open" :title="__('handbook.no_handbooks')" />
                    </div>
                @endif
            </div>
        @endforelse
    </div>

    {{-- In-App Document Reader & Compliance Modal --}}
    @if ($this->selectedHandbook)
        @php
            $selectedEntity = $this->selectedHandbook->asHandbook();
            $selectedLastAck = $this->acknowledgments[$this->selectedHandbook->id] ?? null;
            $selectedIsPending = $selectedEntity->isNewerThan($selectedLastAck);
            $selectedMedia = $this->selectedHandbook->getFirstMedia('handbook_file');
        @endphp

        <x-ts-modal wire="showReaderModal" separator center class="max-w-2xl">
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <div class="bg-primary/10 text-primary flex size-7 items-center justify-center rounded-md">
                        <x-ts-icon name="document-text" class="size-4" />
                    </div>
                    <span class="truncate font-semibold">{{ $selectedEntity->title() }}</span>
                    <span class="text-base-content/50 font-mono text-xs">v{{ $selectedEntity->version() }}</span>
                </div>
            </x-slot:title>

            <div class="space-y-4">
                @if ($selectedMedia)
                    <div class="border-base-content/10 bg-base-200/40 rounded-lg border p-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-base-content/60 font-medium">{{ __('handbook.file') }}:</span>
                            <span class="text-base-content font-mono">{{ $selectedMedia->file_name }} ({{ $selectedMedia->human_readable_size }})</span>
                        </div>
                    </div>
                @endif

                <div>
                    <h4 class="text-base-content mb-2 text-xs font-semibold tracking-wider uppercase">
                        {{ __('handbook.guideline_content') }}
                    </h4>
                    <div class="border-base-content/10 bg-base-100 text-base-content/85 max-h-80 overflow-y-auto rounded-lg border p-4 text-sm leading-relaxed whitespace-pre-line">
                        {{ $this->selectedHandbook->content ?: $selectedEntity->description() ?: __('handbook.no_handbooks') }}
                    </div>
                </div>

                <div class="border-base-content/10 bg-base-200/50 flex items-center justify-between rounded-lg border p-3">
                    <div class="text-base-content/70 flex items-center gap-2 text-xs">
                        <x-ts-icon name="arrow-down-tray" class="size-4" />
                        <span>Dokumen Panduan PDF Resmi</span>
                    </div>
                    <x-ts-button
                        :text="__('handbook.download')"
                        icon="arrow-down-tray"
                        color="secondary"
                        outline
                        xs
                        wire:click="download('{{ $this->selectedHandbook->id }}')"
                    />
                </div>

                @if ($selectedIsPending)
                    <div class="border-warning/30 bg-warning/10 space-y-3 rounded-lg border p-4">
                        <div class="flex items-start gap-2.5">
                            <x-ts-icon name="exclamation-circle" class="text-warning mt-0.5 size-5 shrink-0" />
                            <p class="text-base-content/80 text-xs leading-relaxed">
                                {{ __('handbook.compliance_statement') }}
                            </p>
                        </div>
                        <div class="flex justify-end">
                            <x-ts-button
                                :text="__('handbook.confirm_read_button')"
                                icon="check"
                                color="primary"
                                sm
                                wire:click="acknowledge('{{ $this->selectedHandbook->id }}')"
                            />
                        </div>
                    </div>
                @else
                    <div class="border-success/30 bg-success/10 flex items-center justify-between rounded-lg border p-3.5">
                        <div class="flex items-center gap-2.5">
                            <x-ts-icon name="check-circle" class="text-success size-5 shrink-0" />
                            <span class="text-base-content/80 text-xs font-medium">
                                {{ __('handbook.acknowledged_at', ['date' => $selectedLastAck?->created_at?->translatedFormat('d M Y, H:i') ?? '-']) }}
                            </span>
                        </div>
                        <x-ts-badge :text="__('handbook.status_completed')" color="success" sm />
                    </div>
                @endif
            </div>

            <x-slot:footer>
                <div class="flex justify-end">
                    <x-ts-button
                        :text="__('common.actions.close')"
                        color="secondary"
                        outline
                        sm
                        wire:click="closeReader"
                    />
                </div>
            </x-slot:footer>
        </x-ts-modal>
    @endif
</div>
