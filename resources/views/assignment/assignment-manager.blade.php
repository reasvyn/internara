<div class="animate-in fade-in slide-in-from-bottom-8 duration-1000">
    {{-- Header Section --}}
    <div class="mb-8 flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
        <div>
            <h2 class="tracking-tightest text-base-content text-3xl font-black">{{ __('assignment.heading') }}</h2>
            <p class="text-base-content/40 mt-2 text-[10px] font-black tracking-[0.3em] uppercase">
                {{ __('assignment.subheading') }}
            </p>
        </div>
        <x-ts-button
            :text="__('assignment.create')"
            icon="plus"
            class="shadow-primary/30 h-12 rounded-[2rem] px-8 text-[10px] font-black tracking-[0.2em] uppercase shadow-2xl transition-transform hover:scale-[1.02]"
            color="primary"
            wire:click="create"
        />
    </div>

    {{-- Controls Section --}}
    <div class="mb-8 flex flex-col items-start justify-between gap-6 lg:flex-row lg:items-center">
        <div class="group relative w-full lg:max-w-md">
            <div class="bg-primary/5 absolute inset-0 rounded-[1.5rem] opacity-0 blur-md transition-opacity duration-300 group-focus-within:opacity-100"></div>
            <x-ts-input
                wire:model.live.debounce.300ms="search"
                :placeholder="__('assignment.search_placeholder')"
                icon="magnifying-glass"
                clearable
                class="border-base-content/5 focus:border-primary/30 bg-base-200/50 focus:bg-base-100 relative z-10 h-14 rounded-[1.5rem] transition-all duration-300"
            />
        </div>
        <div class="flex w-full gap-4 lg:w-auto">
            <x-ts-select.native
                wire:model.live="filters.status"
                :options="ts_options(['draft' => __('assignment.statuses.draft'), 'published' => __('assignment.statuses.published'), 'closed' => __('assignment.statuses.closed')], __('assignment.select_status'))"
                class="border-base-content/5 bg-base-200/50 h-14 min-w-[160px] rounded-[1.5rem]"
            />
            <x-ts-select.native
                wire:model.live="filters.is_mandatory"
                :options="ts_options(['yes' => __('assignment.mandatory'), 'no' => __('assignment.optional')], __('assignment.select_mandatory'))"
                class="border-base-content/5 bg-base-200/50 h-14 min-w-[160px] rounded-[1.5rem]"
            />
            <x-ts-select.native
                wire:model.live="filters.assignment_type"
                :options="ts_options(['project' => __('assignment.types.project'), 'report' => __('assignment.types.report'), 'essay' => __('assignment.types.essay')], __('assignment.select_type'))"
                class="border-base-content/5 bg-base-200/50 h-14 min-w-[160px] rounded-[1.5rem]"
            />
        </div>
    </div>

    {{-- Selection Bar --}}
    @if ($this->selected_count > 0)
        <div class="bg-primary/5 border-primary/20 animate-in fade-in slide-in-from-top-4 shadow-primary/5 mb-8 flex flex-col items-center justify-between gap-6 rounded-[2rem] border p-4 shadow-xl backdrop-blur-md duration-500 sm:flex-row">
            <div class="flex items-center gap-5 pl-2">
                <div class="bg-primary text-primary-content shadow-primary/30 flex size-12 items-center justify-center rounded-[1.5rem] text-lg font-black shadow-lg">
                    {{ $this->selected_count }}
                </div>
                <div class="text-center sm:text-left">
                    <h4 class="text-primary text-sm font-black tracking-tight uppercase">
                        {{ __('assignment.records_selected') }}
                    </h4>
                    <p class="mt-1 text-[9px] font-black tracking-[0.3em] uppercase opacity-50">
                        {{ __('assignment.bulk_operations') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-4 pr-2">
                <div class="flex gap-2">
                    <x-ts-button
                        aria-label="{{ __('common.actions.delete_selected') }}"
                        :title="__('common.actions.delete_selected')"
                        icon="trash"
                        class="shadow-error/20 h-10 rounded-xl px-6 text-[10px] font-black tracking-widest text-white uppercase shadow-lg transition-transform hover:scale-105"
                        color="red"
                        wire:click="askDeleteSelected"
                    />
                </div>
                <div class="bg-primary/20 mx-2 h-8 w-px"></div>
                <x-ts-button
                    :text="__('common.actions.cancel')"
                    wire:click="clearSelection"
                    class="hover:bg-base-content/5 rounded-xl text-[10px] font-black tracking-widest uppercase"
                    color="slate" outline
                />
            </div>
        </div>
    @endif

    {{-- Table Section --}}
    <x-ts-card class="card-enterprise !bg-base-100 shadow-base-content/5 border-base-content/5 overflow-hidden border shadow-2xl">
        <div class="table-enterprise overflow-x-auto">
            <x-ts-table
                :headers="$this->headers()"
                :rows="$this->rows()"
                :sort-by="$sortBy"
                with-pagination
                selectable
                wire:model="selectedIds"
                class="table-md w-full whitespace-nowrap"
            >
                @interact('column_title', $assignment)
                    <div class="flex flex-col py-2">
                        <span class="text-base-content text-sm font-black tracking-tight">{{ $assignment->title }}</span>
                        @if ($assignment->description)
                            <span class="mt-0.5 line-clamp-1 max-w-xs text-[10px] opacity-50">{{ $assignment->description }}</span>
                        @endif
                    </div>
                @endinteract

                @interact('column_is_mandatory', $assignment)
                    @if ($assignment->is_mandatory)
                        <x-ts-badge :text="__('assignment.required')" color="red" xs />
                    @else
                        <x-ts-badge :text="__('assignment.optional')" color="gray" xs />
                    @endif
                @endinteract

                @interact('column_status', $assignment)
                    @php
                        $badgeColor = match ($assignment->status->value) {
                            'draft' => 'white',
                            'published' => 'green',
                            'closed' => 'red',
                            default => 'white',
                        };
                    @endphp
                    <x-ts-badge :text="$assignment->status->label()" :color="$badgeColor" xs />
                @endinteract

                @interact('column_due_date', $assignment)
                    <span class="text-sm font-medium"> {{ $assignment->due_date?->format('d M Y') ?? '—' }} </span>
                @endinteract

                @interact('column_action', $assignment)
                    <div class="flex items-center justify-end gap-1 py-2">
                        @if ($assignment->status->value === 'draft')
                            <x-ts-button
                                icon="paper-airplane"
                                class="btn-circle text-success hover:bg-success/10 transition-colors"
                                color="white"
                                sm
                                wire:click="publish('{{ $assignment->id }}')"
                                tooltip="{{ __('assignment.publish_tooltip') }}"
                            />
                        @endif
                        <x-ts-button
                            aria-label="{{ __('common.actions.edit') }}"
                            icon="pencil"
                            class="btn-circle text-primary hover:bg-primary/10 transition-colors"
                            color="white"
                            sm
                            wire:click="edit('{{ $assignment->id }}')"
                            tooltip="{{ __('assignment.edit_tooltip') }}"
                        />
                        <x-ts-button
                            aria-label="{{ __('common.actions.delete') }}"
                            icon="trash"
                            class="btn-circle text-error hover:bg-error/10 transition-colors"
                            color="white"
                            sm
                            wire:click="askDelete('{{ $assignment->id }}')"
                            tooltip="{{ __('assignment.delete_tooltip') }}"
                        />
                    </div>
                @endinteract
            </x-ts-table>
        </div>

        {{-- Assignment Modal --}}
    </x-ts-card>
    <x-ts-modal
        wire="assignmentModal"
        :title="$formData['id'] ? __('assignment.edit') : __('assignment.create')"
        blur
        box-class="rounded-[2.5rem] p-6 border border-base-content/5 shadow-2xl"
    >
        <div class="grid grid-cols-1 gap-6 pt-4">
            <x-ts-input
                :label="__('assignment.title')"
                wire:model="formData.title"
                icon="document-text"
                class="border-base-content/5 focus:border-primary/30 bg-base-200/50 rounded-[1.5rem] py-3"
            />

            <x-ts-select.native
                :label="__('assignment.type')"
                wire:model="formData.assignment_type_id"
                :options="ts_options($this->assignmentTypes->pluck('name', 'id'), __('assignment.type_placeholder'))"
                class="border-base-content/5 focus:border-primary/30 bg-base-200/50 rounded-[1.5rem]"
            />

            <x-ts-select.native
                :label="__('assignment.internship')"
                wire:model="formData.internship_id"
                :options="ts_options($this->internships->pluck('name', 'id'), __('assignment.internship_placeholder'))"
                class="border-base-content/5 focus:border-primary/30 bg-base-200/50 rounded-[1.5rem]"
            />

            <x-ts-input
                :label="__('assignment.due_date')"
                type="date"
                wire:model="formData.due_date"
                icon="calendar"
                class="border-base-content/5 focus:border-primary/30 bg-base-200/50 rounded-[1.5rem] py-3"
            />

            <x-ts-textarea
                :label="__('assignment.description')"
                wire:model="formData.description"
                rows="3"
                class="border-base-content/5 focus:border-primary/30 bg-base-200/50 rounded-[1.5rem]"
            />

            <x-ts-toggle
                :label="__('assignment.is_mandatory')"
                wire:model="formData.is_mandatory"
                class="rounded-xl"
            />
        </div>

        <x-slot:footer>
            <div class="border-base-content/5 flex w-full justify-end gap-4 border-t pt-6">
                <x-ts-button
                    :text="__('common.actions.cancel')"
                    wire:click="$set('assignmentModal', false)"
                    class="rounded-[1.5rem] px-8 text-[10px] font-black tracking-widest uppercase"
                    color="slate" outline
                />
                <x-ts-button
                    :text="__('common.actions.save')"
                    type="submit"
                    class="shadow-primary/20 rounded-[1.5rem] px-10 text-[10px] font-black tracking-[0.2em] uppercase shadow-xl"
                    color="primary"
                    wire:click="save"
                    loading="save"
                />
            </div>
        </x-slot:footer>
    </x-ts-modal>

</div>
