<div class="py-4">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold">{{ __('academic_year.title') }}</h2>
            <p class="text-sm text-base-content/50 mt-1">{{ __('academic_year.subtitle') }}</p>
        </div>
        <x-mary-button
            label="{{ __('academic_year.create') }}"
            icon="o-plus"
            class="btn-primary btn-sm"
            wire:click="$set('showModal', true)"
        />
    </div>

    {{-- Search & Bulk Actions --}}
    <div class="flex items-center gap-3 mb-4">
        <div class="flex-1">
            <x-mary-input
                placeholder="{{ __('academic_year.search_placeholder') }}"
                wire:model.live.debounce.300ms="search"
                icon="o-magnifying-glass"
                clearable
            />
        </div>
        @if($selectedIds !== [])
            <x-mary-button
                label="{{ __('academic_year.delete_selected', ['count' => count($selectedIds)]) }}"
                icon="o-trash"
                class="btn-error btn-sm"
                wire:click="deleteSelected"
                wire:confirm="{{ __('academic_year.confirm_delete_selected', ['count' => count($selectedIds)]) }}"
                spinner="deleteSelected"
            />
        @endif
    </div>

    {{-- List --}}
    <div class="bg-base-100 border border-base-content/10 rounded-xl overflow-hidden">
        @if($years->count())
            <div class="flex items-center gap-3 px-6 py-3 bg-base-200/50 border-b border-base-content/10">
                <x-mary-checkbox
                    :value="true"
                    :checked="count($selectedIds) === $years->count()"
                    wire:click="toggleSelectAll"
                />
                <span class="text-xs font-medium text-base-content/50">
                    @if($selectedIds !== [])
                        {{ __('academic_year.n_selected', ['count' => count($selectedIds)]) }}
                    @else
                        {{ __('academic_year.select_all') }}
                    @endif
                </span>
            </div>
        @endif

        <div class="divide-y divide-base-content/10">
            @forelse($years as $year)
                <div class="flex items-center justify-between px-6 py-4 @if(in_array($year->id, $selectedIds)) bg-primary/5 @endif">
                    <div class="flex items-center gap-4 min-w-0">
                        <x-mary-checkbox
                            :value="$year->id"
                            wire:model.live="selectedIds"
                        />
                        @if($year->is_active)
                            <span class="size-2 rounded-full bg-success shrink-0" title="Active"></span>
                        @else
                            <span class="size-2 rounded-full bg-base-content/20 shrink-0" title="Inactive"></span>
                        @endif
                        <div class="min-w-0">
                            <p class="text-sm font-medium truncate">{{ $year->name }}</p>
                            <p class="text-xs text-base-content/50">
                                {{ $year->start_date->format('d M Y') }} &mdash; {{ $year->end_date->format('d M Y') }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        @if(!$year->is_active)
                            <x-mary-button
                                icon="o-check"
                                class="btn-ghost btn-sm text-success"
                                wire:click="askActivate('{{ $year->id }}')"
                            />
                            <x-mary-button
                                icon="o-trash"
                                class="btn-ghost btn-sm text-error"
                                wire:click="askDestroy('{{ $year->id }}')"
                            />
                        @else
                            <span class="badge badge-sm badge-success">{{ __('academic_year.active') }}</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-6 py-12 text-center">
                    <x-mary-icon name="o-calendar" class="size-10 text-base-content/20 mx-auto mb-3" />
                    <p class="text-sm text-base-content/50">{{ $search ? __('academic_year.empty_search') : __('academic_year.empty') }}</p>
                </div>
            @endforelse
        </div>

        @if($years->hasPages())
            <div class="px-6 py-4 border-t border-base-content/10">
                {{ $years->links() }}
            </div>
        @endif
    </div>

    {{-- Confirm Dialog --}}
    <x-shared::ui.confirm
        wire:model="showConfirm"
        :message="$confirmMessage"
        confirmText="{{ __('common.actions.confirm') }}"
        cancelText="{{ __('common.actions.cancel') }}"
        :confirmClass="$confirmType === 'activate' ? 'btn-primary' : 'btn-error'"
    />

    {{-- Create Modal --}}
    <x-mary-modal wire:model="showModal" title="{{ __('academic_year.new') }}" class="backdrop-blur-sm">
        <div class="space-y-4">
            <x-mary-input
                label="{{ __('academic_year.name') }}"
                wire:model="formData.name"
                placeholder="e.g.: 2025/2026"
            />
            <div class="grid grid-cols-2 gap-4">
                <x-mary-input
                    label="{{ __('academic_year.start_date') }}"
                    type="date"
                    wire:model="formData.start_date"
                />
                <x-mary-input
                    label="{{ __('academic_year.end_date') }}"
                    type="date"
                    wire:model="formData.end_date"
                />
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button label="{{ __('common.actions.cancel') }}" wire:click="resetForm" class="btn-ghost btn-sm" />
            <x-mary-button label="{{ __('common.actions.save') }}" wire:click="store" class="btn-primary btn-sm" spinner="store" />
        </x-slot:actions>
    </x-mary-modal>
</div>
