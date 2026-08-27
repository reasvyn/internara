<div class="py-4">
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="bg-primary/10 text-primary flex size-12 shrink-0 items-center justify-center rounded-xl">
                <x-ts-icon name="calendar-days" class="size-6" />
            </div>
            <div>
                <h2 class="text-xl font-bold">{{ __('academic_year.title') }}</h2>
                <p class="text-base-content/50 mt-0.5 text-sm">{{ __('academic_year.subtitle') }}</p>
            </div>
        </div>
        <x-ts-button :text="__('academic_year.create')" icon="plus" color="primary" sm wire:click="create" />
    </div>

    {{-- Stats --}}
    <div class="mb-6 grid grid-cols-3 gap-4">
        <x-ui::widgets.stat-card
            :title="__('academic_year.stats_total')"
            :value="$stats['total']"
            icon="o-calendar-days"
            color="text-primary"
        />
        <x-ui::widgets.stat-card
            :title="__('academic_year.stats_total_internships')"
            :value="$stats['totalInternships']"
            icon="o-briefcase"
            color="text-info"
        />
        <x-ui::widgets.stat-card
            :title="__('academic_year.stats_with_internships')"
            :value="$stats['withInternships']"
            icon="o-building-library"
            color="text-secondary"
        />
    </div>

    {{-- Search --}}
    <div class="mb-4 flex items-center gap-3">
        <div class="flex-1">
            <x-ts-input
                :placeholder="__('academic_year.search_placeholder')"
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                clearable
            />
        </div>
    </div>

    {{-- Selection Bar --}}
    <x-ui::components.selection-bar>
        <x-ts-button
            :text="__('academic_year.delete_selected')"
            icon="trash"
            color="red"
            sm
            wire:click="askDeleteSelected"
        />
    </x-ui::components.selection-bar>

    {{-- List --}}
    <div class="bg-base-100 border-base-content/10 overflow-hidden rounded-xl border">
        @if ($years->count())
            <div class="bg-base-200/50 border-base-content/10 flex items-center gap-3 border-b px-6 py-3">
                <x-ts-checkbox
                    :value="true"
                    :checked="count($selectedIds) === $years->count()"
                    wire:click="toggleSelectAll"
                />
                <span class="text-base-content/50 text-xs font-medium">
                    @if ($selectedIds !== [])
                        {{ __('academic_year.n_selected', ['count' => count($selectedIds)]) }}
                    @else
                        {{ __('academic_year.select_all') }}
                    @endif
                </span>
            </div>
        @endif

        <div class="divide-base-content/10 divide-y">
            @forelse ($years as $year)
                <div class="flex items-center justify-between px-6 py-4 @if (in_array($year->id, $selectedIds)) bg-primary/5 @endif">
                    <div class="flex min-w-0 items-center gap-4">
                        <x-ts-checkbox :value="$year->id" wire:model.live="selectedIds" />
                        @if ($year->is_active)
                            <span
                                class="bg-success size-2 shrink-0 rounded-full"
                                title="{{ __('academic_year.active') }}"
                            ></span>
                        @else
                            <span
                                class="bg-base-content/20 size-2 shrink-0 rounded-full"
                                title="{{ __('academic_year.inactive') }}"
                            ></span>
                        @endif
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">{{ $year->name }}</p>
                            <p class="text-base-content/50 text-xs">
                                {{ $year->start_date->format('d M Y') }} &mdash; {{ $year->end_date->format('d M Y') }}
                            </p>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        @if (! $year->is_active)
                            <x-ts-button.circle
                                icon="pencil"
                                color="white"
                                sm
                                wire:click="edit('{{ $year->id }}')"
                                :aria-label="__('common.actions.edit')"
                            />
                            <x-ts-button.circle
                                icon="check"
                                color="green"
                                sm
                                wire:click="askActivate('{{ $year->id }}')"
                                :aria-label="__('academic_year.activate')"
                            />
                            <x-ts-button.circle
                                icon="trash"
                                color="red"
                                sm
                                wire:click="askDestroy('{{ $year->id }}')"
                                :aria-label="__('common.actions.delete')"
                            />
                        @else
                            <x-ts-badge :text="__('academic_year.active')" color="green" xs />
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-6 py-12 text-center">
                    <x-ts-icon name="calendar" class="text-base-content/20 mx-auto mb-3 size-10" />
                    <p class="text-base-content/50 text-sm">
                        {{ $search ? __('academic_year.empty_search') : __('academic_year.empty') }}
                    </p>
                </div>
            @endforelse
        </div>

        @if ($years->hasPages())
            <div class="border-base-content/10 border-t px-6 py-4">{{ $years->links() }}</div>
        @endif
    </div>

    {{-- Create / Edit Modal --}}
    <x-ts-modal wire="showModal" :title="$editingYearId ? __('academic_year.edit') : __('academic_year.new')" blur>
        <div class="space-y-4">
            <x-ts-input
                :label="__('academic_year.name')"
                wire:model="form.name"
                :placeholder="__('academic_year.name_placeholder')"
                icon="academic-cap"
            />
            <div class="grid grid-cols-2 gap-4">
                <x-ts-input
                    :label="__('academic_year.start_date')"
                    type="date"
                    wire:model="form.start_date"
                    icon="calendar"
                />
                <x-ts-input
                    :label="__('academic_year.end_date')"
                    type="date"
                    wire:model="form.end_date"
                    icon="calendar-days"
                />
            </div>
        </div>

        <x-slot:footer>
            <div class="flex justify-end gap-2">
                <x-ts-button
                    :text="__('common.actions.cancel')"
                    color="white"
                    sm
                    wire:click="$set('showModal', false)"
                />
                @if ($editingYearId)
                    <x-ts-button
                        :text="__('common.actions.update')"
                        color="primary"
                        sm
                        wire:click="update"
                        loading="update"
                    />
                @else
                    <x-ts-button
                        :text="__('common.actions.save')"
                        color="primary"
                        sm
                        wire:click="store"
                        loading="store"
                    />
                @endif
            </div>
        </x-slot:footer>
    </x-ts-modal>

    @include('setup.components.academic-year-guide')
</div>
