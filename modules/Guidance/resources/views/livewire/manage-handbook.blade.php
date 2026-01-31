<div>
    <x-ui::header title="{{ __('guidance::ui.manage_title') }}" subtitle="{{ __('guidance::ui.manage_subtitle') }}">
        <x-slot:actions>
            <x-ui::button label="{{ __('guidance::ui.add_handbook') }}" icon="tabler.plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>
    </x-ui::header>

    <x-ui::card shadow>
        <x-ui::tabs selected="list">
            <x-ui::tab name="list" label="{{ __('guidance::ui.list_tab') }}" icon="tabler.list">
                <div class="flex flex-col md:flex-row gap-4 my-6">
                    <div class="flex-1">
                        <x-ui::input icon="tabler.search" placeholder="{{ __('schedule::ui.search_placeholder') }}" wire:model.live.debounce.300ms="search" />
                    </div>
                </div>

                <x-ui::table :headers="$headers" :rows="$handbooks" with-pagination>
                    @scope('cell_is_mandatory', $handbook)
                        @if($handbook->is_mandatory)
                            <x-ui::badge label="{{ __('guidance::ui.mandatory') }}" class="badge-error" />
                        @else
                            <x-ui::badge label="{{ __('guidance::ui.optional') }}" class="badge-ghost" />
                        @endif
                    @endscope

                    @scope('cell_is_active', $handbook)
                        @if($handbook->is_active)
                            <x-ui::badge label="{{ __('guidance::ui.active') }}" class="badge-success" />
                        @else
                            <x-ui::badge label="{{ __('guidance::ui.draft') }}" class="badge-warning" />
                        @endif
                    @endscope

                    @scope('actions', $handbook)
                        <div class="flex items-center gap-2">
                            <x-ui::button icon="tabler.edit" class="btn-ghost btn-sm btn-circle" wire:click="edit('{{ $handbook->id }}')" />
                            <x-ui::button 
                                icon="tabler.trash" 
                                class="btn-ghost btn-sm btn-circle text-error" 
                                wire:confirm="{{ __('guidance::ui.delete_confirm') }}"
                                wire:click="delete('{{ $handbook->id }}')" 
                            />
                        </div>
                    @endscope
                </x-ui::table>
            </x-ui::tab>

            <x-ui::tab name="monitoring" label="{{ __('guidance::ui.monitoring_tab') }}" icon="tabler.users-check">
                <div class="py-4">
                    <livewire:guidance::tables.handbook-acknowledgement-table />
                </div>
            </x-ui::tab>
        </x-ui::tabs>
    </x-ui::card>

    {{-- Handbook Form Modal --}}
    <x-ui::modal wire:model="showForm" title="{{ $selectedHandbookId ? __('guidance::ui.edit_handbook') : __('guidance::ui.new_handbook') }}" separator>
        <livewire:guidance::handbook-form :handbook-id="$selectedHandbookId" wire:key="{{ $selectedHandbookId ?? 'new' }}" />
    </x-ui::modal>
</div>
