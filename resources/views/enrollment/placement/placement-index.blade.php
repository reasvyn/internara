<x-ui::components.record-manager :title="__('placement.title')" :subtitle="__('placement.subtitle')">
    <x-slot:headerActions>
        <x-ts-button :text="__('placement.add_placement')" icon="plus" color="primary" sm wire:click="create" />
    </x-slot:headerActions>

    <x-slot:extraMenu>
        <x-ts-dropdown.items :text="__('common.actions.import')" />
        <x-ts-dropdown.items :text="__('common.actions.export')" icon="arrow-down-tray" />
        <x-ts-dropdown.items :text="__('common.actions.template')" icon="document-arrow-down" />
    </x-slot:extraMenu>

    <x-slot:stats>
        <x-ui::widgets.stat-card icon="briefcase" :title="__('placement.stats.total')" :value="$this->stats['total']" />
        <x-ui::widgets.stat-card
            icon="user-group"
            :title="__('placement.stats.total_quota')"
            :value="$this->stats['total_quota']"
        />
        <x-ui::widgets.stat-card
            icon="check-circle"
            :title="__('placement.stats.filled')"
            :value="$this->stats['filled']"
            color="text-success"
        />
        <x-ui::widgets.stat-card
            icon="plus-circle"
            :title="__('placement.stats.available')"
            :value="$this->stats['available']"
        />
    </x-slot:stats>

    <x-slot:filters>
        <x-ts-select.native
            wire:model.live="filters.company_id"
            :options="ts_options($this->companies, __('placement.filter_by_company'))"
            clearable
        />
        <x-ts-select.native
            wire:model.live="filters.internship_id"
            :options="ts_options($this->internships, __('placement.filter_by_batch'))"
            clearable
        />
    </x-slot:filters>

    <x-ui::components.selection-bar>
        <x-ts-dropdown>
            <x-slot:action>
                <x-ts-button
                    icon="chevron-down"
                    class="font-medium"
                    color="primary"
                    sm
                    :text="__('common.actions.bulk_actions')"
                    x-on:click="show = ! show"
                />
            </x-slot:action>
            <div class="w-48 p-1.5">
                <x-ts-dropdown.items
                    :text="__('common.actions.delete_selected')"
                    icon="trash"
                    class="text-error"
                    wire:click="askDeleteSelected"
                />
            </div>
        </x-ts-dropdown>
    </x-ui::components.selection-bar>

    <div class="overflow-x-auto">
        <x-ts-table
            :headers="$this->headers()"
            :rows="$this->rows()"
            :sort-by="$sortBy"
            with-pagination
            selectable
            wire:model.live="selectedIds"
            class="table-sm"
        >
            @interact('column_quota', $placement)
                @php
                    $isFull = $placement->quota > 0 && $placement->filled_quota >= $placement->quota;
                @endphp
                <div class="inline-flex items-center font-mono text-xs font-semibold px-2 py-0.5 rounded-md {{ $isFull ? 'bg-error/10 text-error' : 'bg-base-200 text-base-content' }}">
                    <span>{{ $placement->filled_quota }}</span>
                    <span class="text-base-content/40 mx-0.5">/</span>
                    <span>{{ $placement->quota }}</span>
                </div>
            @endinteract

            @interact('column_filled_quota', $placement)
                @php
                    $quota = max(1, (int) $placement->quota);
                    $filled = (int) $placement->filled_quota;
                    $percent = min(100, (int) round(($filled / $quota) * 100));
                    $colorClass = match (true) {
                        $percent >= 100 => 'progress-error',
                        $percent >= 80 => 'progress-warning',
                        default => 'progress-primary',
                    };
                @endphp
                <div class="flex min-w-[130px] items-center gap-2.5">
                    <progress class="progress {{ $colorClass }} h-2 flex-1" value="{{ $percent }}" max="100"></progress>
                    <span class="text-base-content/70 w-10 text-right font-mono text-xs font-semibold tabular-nums">{{ $percent }}%</span>
                </div>
            @endinteract

            @interact('column_action', $placement)
                <x-ui::components.table-actions>
                    <x-ts-button.circle
                        icon="pencil"
                        color="primary"
                        outline
                        xs
                        wire:click="edit('{{ $placement->id }}')"
                        :aria-label="__('common.actions.edit')"
                        tooltip="{{ __('common.actions.edit') }}"
                    />
                    <x-ts-button.circle
                        icon="trash"
                        color="red"
                        outline
                        xs
                        wire:click="askDelete('{{ $placement->id }}')"
                        :aria-label="__('common.actions.delete')"
                        tooltip="{{ __('common.actions.delete') }}"
                    />
                </x-ui::components.table-actions>
            @endinteract
        </x-ts-table>
    </div>

    <x-slot:modal>
        <x-ts-modal
            wire="showModal"
            :title="$form->id ? __('placement.edit_placement') : __('placement.new_placement')"
            blur
        >
            <form wire:submit="save">
                <div class="space-y-5">
                    <x-ts-input
                        :label="__('placement.name')"
                        wire:model="form.name"
                        :placeholder="__('placement.name_placeholder')"
                    />
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-ts-select.native
                            :label="__('placement.company')"
                            wire:model="form.company_id"
                            :options="ts_options($this->companies, __('placement.company_placeholder'))"
                        />
                        <x-ts-select.native
                            :label="__('placement.batch')"
                            wire:model="form.internship_id"
                            :options="ts_options($this->internships, __('placement.internship_placeholder'))"
                        />
                        <x-ts-input :label="__('placement.quota')" type="number" wire:model="form.quota" />
                    </div>
                    <x-ts-textarea
                        :label="__('placement.worksite_address')"
                        wire:model="form.address"
                        rows="2"
                        :placeholder="__('placement.address_placeholder')"
                    />
                    <x-ts-textarea :label="__('placement.job_description')" wire:model="form.description" rows="3" />
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('showModal', false)"
                        color="slate"
                        outline
                        sm
                    />
                    <x-ts-button :text="__('placement.save')" color="primary" sm type="submit" loading="save" />
                </div>
            </form>
        </x-ts-modal>
    </x-slot:modal>

    @include('enrollment.placement.components.placement-guide')
</x-ui::components.record-manager>
