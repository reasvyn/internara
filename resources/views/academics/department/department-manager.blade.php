<div>
    <x-ui::ui.record-manager :title="__('department.title')" :subtitle="__('department.subtitle')">
        <x-slot:headerActions>
            <x-ts-button :text="__('department.add')" icon="plus" color="primary" sm wire:click="create" />
        </x-slot:headerActions>

        <x-slot:extraMenu>
            <x-ts-dropdown.items
                :text="__('common.actions.import')"
                icon="arrow-up-tray"
                @click="document.getElementById('import-csv').click()"
            />
            <input id="import-csv" type="file" accept=".csv" wire:model="importFile" class="hidden" />
            <x-ts-dropdown.items :text="__('common.actions.export')" icon="arrow-down-tray" wire:click="export" />
            <x-ts-dropdown.items
                :text="__('common.actions.template')"
                icon="document-arrow-down"
                wire:click="downloadTemplate"
            />
        </x-slot:extraMenu>

        <x-slot:stats>
            <x-ui::widgets.stat-card
                :title="__('department.stats.total')"
                :value="$stats['total']"
                icon="building-library"
                color="text-primary"
                class="lg:col-span-2"
            />
            <x-ui::widgets.stat-card
                :title="__('department.stats.with_students')"
                :value="$stats['with_internships']"
                icon="users"
                color="text-secondary"
                class="lg:col-span-2"
            />
        </x-slot:stats>

        <x-ui::ui.selection-bar>
            <x-ts-dropdown position="bottom-end">
                <x-slot:action>
                    <x-ts-button icon="chevron-down" color="primary" sm :text="__('common.actions.bulk_actions')" / x-on:click="show = ! show">
                </x-slot:action>
                <x-ts-dropdown.items
                    :text="__('common.actions.export_selected')"
                    icon="arrow-down-tray"
                    wire:click="exportSelected"
                />
                <hr class="border-base-content/10 my-1" />
                <x-ts-dropdown.items
                    :text="__('common.actions.delete_selected')"
                    icon="trash"
                    wire:click="askDeleteSelected"
                />
            </x-ts-dropdown>
        </x-ui::ui.selection-bar>

        <div class="overflow-x-auto">
            <x-ts-table
                :headers="$this->headers()"
                :rows="$this->rows()"
                :sort-by="$sortBy"
                with-pagination
                selectable
                wire:model="selectedIds"
                class="table-sm"
            >
                @interact('column_description', $department)
                    <span class="text-base-content/60 text-sm">
                        {{ Str::limit($department->description ?? '—', 50) }}
                    </span>
                @endinteract

                @interact('column_created_at', $department)
                    <time
                        datetime="{{ $department->created_at->toIso8601String() }}"
                        class="text-base-content/50 text-sm"
                    >
                        {{ $department->created_at->format('M d, Y') }}
                    </time>
                @endinteract

                @interact('column_action', $department)
                    <div class="flex justify-end gap-1">
                        <x-ts-button.circle
                            icon="pencil"
                            color="white"
                            sm
                            wire:click="edit('{{ $department->id }}')"
                            :aria-label="__('common.actions.edit')"
                        />
                        <x-ts-button.circle
                            icon="trash"
                            color="red"
                            sm
                            wire:click="askDelete('{{ $department->id }}')"
                            :aria-label="__('common.actions.delete')"
                        />
                    </div>
                @endinteract
            </x-ts-table>
        </div>

        <x-slot:modal>
            <x-ts-modal wire="showModal" :title="$form->id ? __('department.edit') : __('department.new')" blur>
                <form wire:submit="save">
                    <div class="space-y-5">
                        <x-ts-input
                            :label="__('department.name')"
                            wire:model="form.name"
                            :placeholder="__('department.name_placeholder')"
                            icon="building-library"
                        />
                        <x-ts-textarea :label="__('department.description')" wire:model="form.description" rows="3" />
                    </div>
                    <div class="mt-6 flex justify-end gap-2">
                        <x-ts-button
                            :text="__('common.actions.cancel')"
                            color="white"
                            sm
                            wire:click="$set('showModal', false)"
                        />
                        <x-ts-button :text="__('department.save')" color="primary" sm type="submit" loading="save" />
                    </div>
                </form>
            </x-ts-modal>
        </x-slot:modal>
    </x-ui::ui.record-manager>

    @include('setup.components.department-guide')
</div>
