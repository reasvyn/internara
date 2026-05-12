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

    {{-- List --}}
    <div class="bg-base-100 border border-base-content/10 rounded-xl">
        <div class="divide-y divide-base-content/10">
            @forelse($years as $year)
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center gap-4 min-w-0">
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
                                wire:click="activate('{{ $year->id }}')"
                                wire:confirm="{{ __('academic_year.confirm_activate') }}"
                            />
                            <x-mary-button
                                icon="o-trash"
                                class="btn-ghost btn-sm text-error"
                                wire:click="destroy('{{ $year->id }}')"
                                wire:confirm="{{ __('academic_year.confirm_delete') }}"
                            />
                        @else
                            <span class="badge badge-sm badge-success">{{ __('academic_year.active') }}</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-6 py-12 text-center">
                    <x-mary-icon name="o-calendar" class="size-10 text-base-content/20 mx-auto mb-3" />
                    <p class="text-sm text-base-content/50">{{ __('academic_year.empty') }}</p>
                </div>
            @endforelse
        </div>

        @if($years->hasPages())
            <div class="px-6 py-4 border-t border-base-content/10">
                {{ $years->links() }}
            </div>
        @endif
    </div>

    {{-- Create Modal --}}
    <x-mary-modal wire:model="showModal" title="{{ __('academic_year.new') }}" class="backdrop-blur-sm">
        <div class="space-y-4">
            <x-mary-input
                label="{{ __('academic_year.name') }}"
                wire:model="name"
                placeholder="e.g.: 2025/2026"
            />
            <div class="grid grid-cols-2 gap-4">
                <x-mary-input
                    label="{{ __('academic_year.start_date') }}"
                    type="date"
                    wire:model="start_date"
                />
                <x-mary-input
                    label="{{ __('academic_year.end_date') }}"
                    type="date"
                    wire:model="end_date"
                />
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button label="{{ __('common.actions.cancel') }}" wire:click="resetForm" class="btn-ghost btn-sm" />
            <x-mary-button label="{{ __('common.actions.save') }}" wire:click="store" class="btn-primary btn-sm" spinner="store" />
        </x-slot:actions>
    </x-mary-modal>
</div>
