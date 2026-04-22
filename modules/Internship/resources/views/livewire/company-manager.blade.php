<div>
    <x-ui::header 
        wire:key="company-manager-header"
        :title="__('internship::ui.company_title')" 
        :subtitle="__('internship::ui.company_subtitle')"
    >
        <x-slot:actions wire:key="company-manager-actions">
            <div class="flex items-center gap-3">
                <x-ui::button :label="__('ui::common.refresh')" icon="tabler.refresh" variant="secondary" wire:click="refreshRecords" spinner="refreshRecords" />
                <x-ui::button :label="__('internship::ui.add_company')" icon="tabler.plus" class="btn-primary" wire:click="add" />
            </div>
        </x-slot:actions>
    </x-ui::header>

    <x-ui::card>
        {{-- Search & Filters Bar --}}
        <div class="mb-6 space-y-4">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                {{-- Search Input --}}
                <div class="w-full md:w-1/3">
                    <x-ui::input 
                        placeholder="{{ __('internship::ui.search_company') }}" 
                        icon="tabler.search" 
                        wire:model.live.debounce.300ms="search" 
                        clearable 
                    />
                </div>

                {{-- Filters Dropdown --}}
                <x-ui::dropdown class="w-full md:w-auto">
                    <x-slot name="trigger">
                        <x-ui::button 
                            :label="__('ui::common.filters')" 
                            icon="tabler.filter" 
                            variant="secondary"
                            class="w-full md:w-auto"
                        >
                            @if($this->activeFilterCount > 0)
                                <span class="badge badge-primary badge-sm">{{ $this->activeFilterCount }}</span>
                            @endif
                        </x-ui::button>
                    </x-slot>

                    <div class="dropdown-content w-72 p-4 shadow bg-base-100 rounded-lg border border-base-200 space-y-4">
                        {{-- Business Field Filter --}}
                        <x-ui::select
                            label="{{ __('internship::ui.business_field') }}"
                            wire:model.live="filterBusinessField"
                            :options="$this->businessFieldOptions"
                            placeholder="{{ __('ui::common.all') }}"
                        />

                        {{-- Email Filter --}}
                        <x-ui::input
                            label="{{ __('ui::common.email') }}"
                            wire:model.live.debounce.300ms="filterEmail"
                            placeholder="{{ __('ui::common.search_email') }}"
                            clearable
                        />

                        {{-- Reset Button --}}
                        @if($this->activeFilterCount > 0)
                            <x-ui::button 
                                :label="__('ui::common.reset_filters')" 
                                icon="tabler.x" 
                                variant="secondary"
                                class="w-full"
                                wire:click="resetFilters"
                            />
                        @endif
                    </div>
                </x-ui::dropdown>
            </div>

            {{-- Mass Actions Bar (visible when records selected) --}}
            @if(count($selectedRecords) > 0)
                <div class="alert alert-info gap-4">
                    <x-ui::icon name="tabler.info-circle" class="size-5" />
                    <div class="flex-1">
                        <p class="font-semibold">{{ __('internship::ui.selected_count', ['count' => count($selectedRecords)]) }}</p>
                    </div>
                    <div class="flex gap-2">
                        <x-ui::select 
                            wire:model="massAction"
                            :options="[
                                ['value' => '', 'label' => __('internship::ui.select_action')],
                                ['value' => 'export', 'label' => __('internship::ui.export')],
                                ['value' => 'delete', 'label' => __('internship::ui.bulk_delete')],
                            ]"
                            class="select-sm"
                        />
                        <x-ui::button 
                            :label="__('ui::common.apply')" 
                            icon="tabler.arrow-right"
                            class="btn-sm btn-primary"
                            wire:click="executeMassAction"
                            :disabled="!$massAction || empty($selectedRecords)"
                        />
                        <x-ui::button 
                            :label="__('ui::common.cancel')" 
                            icon="tabler.x"
                            variant="secondary"
                            class="btn-sm"
                            wire:click="$set('selectedRecords', []); $set('selectAll', false);"
                        />
                    </div>
                </div>
            @endif
        </div>

        {{-- Companies Table --}}
        <div class="w-full overflow-auto rounded-xl border border-base-200 bg-base-100 shadow-sm max-h-[60vh]">
            <table class="table table-zebra table-md w-full">
                <thead>
                    <tr>
                        {{-- Select All Checkbox --}}
                        <th class="w-12">
                            <input 
                                type="checkbox" 
                                class="checkbox checkbox-primary"
                                wire:model="selectAll"
                                wire:change="toggleSelectAll"
                            />
                        </th>
                        <th class="cursor-pointer hover:bg-base-200" wire:click="$set('sortBy', ['column' => 'name', 'direction' => $sortBy['direction'] === 'asc' ? 'desc' : 'asc'])">
                            {{ __('ui::common.name') }}
                            @if($sortBy['column'] === 'name')
                                <x-ui::icon :name="$sortBy['direction'] === 'asc' ? 'tabler.sort-ascending' : 'tabler.sort-descending'" class="inline size-4" />
                            @endif
                        </th>
                        <th>{{ __('internship::ui.business_field') }}</th>
                        <th>{{ __('ui::common.phone') }}</th>
                        <th>{{ __('ui::common.email') }}</th>
                        <th>{{ __('ui::common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $company)
                        <tr class="{{ in_array($company->id, $selectedRecords) ? 'bg-base-200' : '' }}">
                            {{-- Checkbox --}}
                            <td>
                                <input 
                                    type="checkbox" 
                                    class="checkbox checkbox-primary"
                                    value="{{ $company->id }}"
                                    wire:model="selectedRecords"
                                />
                            </td>
                            <td class="font-semibold">{{ $company->name }}</td>
                            <td>{{ $company->business_field ?? '-' }}</td>
                            <td>{{ $company->phone ?? '-' }}</td>
                            <td class="text-sm">{{ $company->email ?? '-' }}</td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <x-ui::button icon="tabler.edit" variant="tertiary" class="text-info btn-xs" wire:click="edit('{{ $company->id }}')" tooltip="{{ __('ui::common.edit') }}" />
                                    <x-ui::button icon="tabler.trash" variant="tertiary" class="text-error btn-xs" wire:click="discard('{{ $company->id }}')" tooltip="{{ __('ui::common.delete') }}" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8">
                                <p class="text-base-content/60">{{ __('internship::ui.no_companies') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($records->hasPages())
            <div class="mt-4 flex items-center justify-between">
                <div class="text-sm text-base-content/60">
                    {{ __('ui::common.showing', ['from' => $records->firstItem(), 'to' => $records->lastItem(), 'total' => $records->total()]) }}
                </div>
                {{ $records->links('pagination::tailwind') }}
            </div>
        @endif
    </x-ui::card>

    {{-- Form Modal --}}
    <x-ui::modal id="company-form-modal" wire:model="formModal" title="{{ $form['id'] ? __('internship::ui.edit_company') : __('internship::ui.add_company') }}">
        <x-ui::form wire:submit="save">
            <x-ui::input label="{{ __('internship::ui.company_name') }}" wire:model="form.name" required />
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui::input label="{{ __('internship::ui.business_field') }}" wire:model="form.business_field" />
                <x-ui::input label="{{ __('internship::ui.leader_name') }}" wire:model="form.leader_name" />
            </div>

            <x-ui::textarea label="{{ __('internship::ui.company_address') }}" wire:model="form.address" rows="2" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui::input label="{{ __('internship::ui.company_phone') }}" wire:model="form.phone" />
                <x-ui::input label="{{ __('internship::ui.company_fax') }}" wire:model="form.fax" />
            </div>
            
            <x-ui::input label="{{ __('internship::ui.company_email') }}" type="email" wire:model="form.email" />

            <x-slot:actions>
                <x-ui::button label="{{ __('ui::common.cancel') }}" wire:click="$set('formModal', false)" />
                <x-ui::button label="{{ __('ui::common.save') }}" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>

    {{-- Confirm Delete Modal --}}
    <x-ui::modal id="company-confirm-modal" wire:model="confirmModal" title="{{ __('ui::common.confirm') }}">
        <p>{{ __('internship::ui.delete_company_confirm') }}</p>
        <x-slot:actions>
            <x-ui::button label="{{ __('ui::common.cancel') }}" wire:click="$set('confirmModal', false)" />
            <x-ui::button label="{{ __('ui::common.delete') }}" class="btn-error" wire:click="remove('{{ $recordId }}')" spinner="remove" />
        </x-slot:actions>
    </x-ui::modal>
</div>
