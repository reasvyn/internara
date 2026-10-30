<x-ui::components.record-manager :title="__('handbook.title')" :subtitle="__('handbook.subtitle')">
    <x-slot:headerActions>
        <x-ts-button :text="__('handbook.create')" icon="plus" color="primary" sm wire:click="create" />
    </x-slot:headerActions>

    <div class="border-base-content/10 mb-5 flex flex-col justify-between gap-3 border-b pb-4 sm:flex-row sm:items-center">
        <div class="flex items-center gap-2">
            <button
                type="button"
                wire:click="setTab('catalog')"
                @class([
                    'cursor-pointer px-3.5 py-1.5 text-xs font-semibold rounded-md transition-colors flex items-center gap-1.5',
                    'bg-primary text-primary-content shadow-2xs' => $tab === 'catalog',
                    'bg-base-200/80 text-base-content/70 hover:bg-base-200 hover:text-base-content' => $tab !== 'catalog',
                ])
            >
                <x-ts-icon name="book-open" class="size-4" />
                <span>{{ __('handbook.tab_catalog') }}</span>
            </button>
            <button
                type="button"
                wire:click="setTab('manage')"
                @class([
                    'cursor-pointer px-3.5 py-1.5 text-xs font-semibold rounded-md transition-colors flex items-center gap-1.5',
                    'bg-primary text-primary-content shadow-2xs' => $tab === 'manage',
                    'bg-base-200/80 text-base-content/70 hover:bg-base-200 hover:text-base-content' => $tab !== 'manage',
                ])
            >
                <x-ts-icon name="table-cells" class="size-4" />
                <span>{{ __('handbook.tab_manage') }}</span>
            </button>
        </div>

        @if ($tab === 'catalog')
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
                @php
                    $filters = [
                        'all' => __('handbook.audience_all'),
                        'student' => __('handbook.audience_student'),
                        'teacher' => __('handbook.audience_teacher'),
                        'supervisor' => __('handbook.audience_supervisor'),
                        'admin' => __('handbook.audience_admin'),
                    ];
                @endphp
                @foreach ($filters as $key => $label)
                    <button
                        type="button"
                        wire:click="setAudienceFilter('{{ $key }}')"
                        @class([
                            'cursor-pointer px-2.5 py-1 text-xs font-medium rounded-md transition-colors',
                            'bg-primary text-primary-content shadow-2xs' => $audienceFilter === $key,
                            'bg-base-200/60 text-base-content/70 hover:bg-base-200 hover:text-base-content' => $audienceFilter !== $key,
                        ])
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    @if ($tab === 'catalog')
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($this->catalogHandbooks as $handbook)
                @php
                    $aud = $handbook->metadata['target_audience'] ?? 'all';
                    $audLabel = match ($aud) {
                        'student' => __('handbook.audience_student'),
                        'teacher' => __('handbook.audience_teacher'),
                        'supervisor' => __('handbook.audience_supervisor'),
                        'admin' => __('handbook.audience_admin'),
                        default => __('handbook.audience_all'),
                    };
                    $media = $handbook->relationLoaded('media') ? $handbook->getFirstMedia('handbook_file') : null;
                @endphp

                <div class="border-base-content/10 bg-base-100 hover:border-base-content/20 flex flex-col justify-between rounded-lg border p-5 shadow-2xs transition-all duration-200 hover:shadow-xs">
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5">
                                <span class="badge badge-outline badge-sm text-base-content/60 font-mono text-xs">v{{ $handbook->version ?? 1 }}.0</span>
                                <x-ts-badge :text="$audLabel" color="secondary" outline sm />
                            </div>

                            <x-ts-badge
                                :text="$handbook->is_active ? __('handbook.active') : __('handbook.inactive')"
                                :class="$handbook->is_active ? 'badge-success' : 'badge-ghost'"
                                sm
                            />
                        </div>

                        <h3 class="text-base-content mt-3 line-clamp-1 text-base font-semibold tracking-tight">
                            {{ $handbook->title }}
                        </h3>

                        @if (! empty($handbook->metadata['description']))
                            <p class="text-base-content/70 mt-1.5 line-clamp-2 text-xs leading-relaxed">
                                {{ $handbook->metadata['description'] }}
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
                        </div>
                    </div>

                    <div class="border-base-content/10 mt-5 flex items-center justify-between gap-2 border-t pt-3.5">
                        <div class="flex items-center gap-1">
                            <x-ts-button
                                :title="__('handbook.download')"
                                icon="arrow-down-tray"
                                color="secondary"
                                outline
                                sm
                                wire:click="download('{{ $handbook->id }}')"
                            />
                            <x-ts-button
                                :title="__('handbook.edit')"
                                icon="pencil"
                                color="secondary"
                                outline
                                sm
                                wire:click="edit('{{ $handbook->id }}')"
                            />
                        </div>

                        <x-ts-button
                            :text="__('handbook.preview_handbook')"
                            icon="book-open"
                            color="primary"
                            sm
                            wire:click="openReader('{{ $handbook->id }}')"
                        />
                    </div>
                </div>
            @empty
                <div class="col-span-full">
                    <div class="border-base-content/10 bg-base-100 flex flex-col items-center justify-center rounded-lg border p-8 text-center">
                        <x-ui::widgets.empty-state icon="book-open" :title="__('handbook.no_handbooks')" />
                    </div>
                </div>
            @endforelse
        </div>
    @else
        <div class="overflow-x-auto">
            <x-ts-table
                :headers="$this->headers()"
                :rows="$this->rows()"
                :sort-by="$sortBy"
                with-pagination
                class="table-sm"
            >
                @interact('column_title', $h)
                    <div class="flex flex-col">
                        <span class="text-sm font-medium">{{ $h->title }}</span>
                        <span class="text-base-content/50 text-xs">v{{ $h->version }}</span>
                    </div>
                @endinteract

                @interact('column_audience', $h)
                    @php
                        $audVal = $h->metadata['target_audience'] ?? 'all';
                        $audLbl = match ($audVal) {
                            'student' => __('handbook.audience_student'),
                            'teacher' => __('handbook.audience_teacher'),
                            'supervisor' => __('handbook.audience_supervisor'),
                            'admin' => __('handbook.audience_admin'),
                            default => __('handbook.audience_all'),
                        };
                    @endphp
                    <span class="text-sm">{{ $audLbl }}</span>
                @endinteract

                @interact('column_is_active', $h)
                    <x-ts-badge
                        :text="$h->is_active ? __('handbook.active') : __('handbook.inactive')"
                        :class="$h->is_active ? 'badge-success' : 'badge-ghost'"
                    />
                @endinteract

                @interact('column_action', $h)
                    <div class="flex items-center gap-1">
                        <x-ts-button
                            icon="eye"
                            color="secondary"
                            outline
                            xs
                            :title="__('handbook.preview_handbook')"
                            wire:click="openReader('{{ $h->id }}')"
                        />
                        <x-ts-button
                            icon="arrow-down-tray"
                            color="secondary"
                            outline
                            xs
                            :title="__('handbook.download')"
                            wire:click="download('{{ $h->id }}')"
                        />
                        <x-ui::components.table-action action="edit" wire:click="edit('{{ $h->id }}')" />
                        <x-ui::components.table-action action="delete" wire:click="askDelete('{{ $h->id }}')" />
                    </div>
                @endinteract
            </x-ts-table>
        </div>
    @endif

    {{-- In-App Document Reader Modal --}}
    @if ($this->selectedHandbook)
        <x-ts-modal wire="showReaderModal" separator center class="max-w-2xl">
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <div class="bg-primary/10 text-primary flex size-7 items-center justify-center rounded-md">
                        <x-ts-icon name="document-text" class="size-4" />
                    </div>
                    <span class="truncate font-semibold">{{ $this->selectedHandbook->title }}</span>
                    <span class="text-base-content/50 font-mono text-xs">v{{ $this->selectedHandbook->version }}</span>
                </div>
            </x-slot:title>

            <div class="space-y-4">
                @php
                    $modalAud = $this->selectedHandbook->metadata['target_audience'] ?? 'all';
                    $modalAudLbl = match ($modalAud) {
                        'student' => __('handbook.audience_student'),
                        'teacher' => __('handbook.audience_teacher'),
                        'supervisor' => __('handbook.audience_supervisor'),
                        'admin' => __('handbook.audience_admin'),
                        default => __('handbook.audience_all'),
                    };
                    $modalMedia = $this->selectedHandbook->getFirstMedia('handbook_file');
                @endphp

                <div class="flex items-center gap-2">
                    <x-ts-badge :text="__('handbook.target_audience').': '.$modalAudLbl" color="secondary" outline sm />
                    <x-ts-badge
                        :text="$this->selectedHandbook->is_active ? __('handbook.active') : __('handbook.inactive')"
                        :class="$this->selectedHandbook->is_active ? 'badge-success' : 'badge-ghost'"
                        sm
                    />
                </div>

                @if ($modalMedia)
                    <div class="border-base-content/10 bg-base-200/40 rounded-lg border p-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-base-content/60 font-medium">{{ __('handbook.file') }}:</span>
                            <span class="text-base-content font-mono">{{ $modalMedia->file_name }} ({{ $modalMedia->human_readable_size }})</span>
                        </div>
                    </div>
                @endif

                <div>
                    <h4 class="text-base-content mb-2 text-xs font-semibold tracking-wider uppercase">
                        {{ __('handbook.guideline_content') }}
                    </h4>
                    <div class="border-base-content/10 bg-base-100 text-base-content/85 max-h-80 overflow-y-auto rounded-lg border p-4 text-sm leading-relaxed whitespace-pre-line">
                        {{ $this->selectedHandbook->content ?: ($this->selectedHandbook->metadata['description'] ?? __('handbook.no_handbooks')) }}
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
            </div>

            <x-slot:footer>
                <div class="flex justify-end gap-2">
                    <x-ts-button
                        :text="__('handbook.edit')"
                        icon="pencil"
                        color="primary"
                        sm
                        wire:click="edit('{{ $this->selectedHandbook->id }}')"
                    />
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

    <x-slot:modal>
        <x-ts-modal wire="showModal" :title="$form->id ? __('handbook.edit') : __('handbook.create')" separator blur>
            <form wire:submit="save" class="space-y-5">
                <x-ts-input :label="__('handbook.title_field')" wire:model="form.title" icon="document-text" />
                <x-ts-select.native
                    :label="__('handbook.target_audience')"
                    wire:model="form.audience"
                    :options="ts_options($this->audienceOptions)"
                />
                <x-ts-textarea :label="__('handbook.content_field')" wire:model="form.description" rows="3" />
                <x-ts-toggle :label="__('handbook.active')" wire:model="form.isActive" />
                <x-ts-upload :label="__('handbook.file')" wire:model="uploadFile" />

                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('showModal', false)"
                        color="slate"
                        outline
                        sm
                    />
                    <x-ts-button :text="__('common.actions.save')" color="primary" sm type="submit" loading="save" />
                </div>
            </form>
        </x-ts-modal>
    </x-slot:modal>
    @include('document.handbook.components.handbook-guide')
</x-ui::components.record-manager>
