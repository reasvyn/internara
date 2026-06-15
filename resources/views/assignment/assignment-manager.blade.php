<div class="animate-in fade-in slide-in-from-bottom-8 duration-1000">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-8 gap-4">
        <div>
            <h2 class="text-3xl font-black tracking-tightest text-base-content">Assignments</h2>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-base-content/40 mt-2">Manage internship tasks</p>
        </div>
        <x-mary-button label="New Assignment" icon="o-plus" class="btn-primary rounded-[2rem] font-black uppercase tracking-[0.2em] text-[10px] px-8 h-12 shadow-2xl shadow-primary/30 hover:scale-[1.02] transition-transform" wire:click="create" />
    </div>

    {{-- Controls Section --}}
    <div class="mb-8 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
        <div class="w-full lg:max-w-md relative group">
            <div class="absolute inset-0 bg-primary/5 rounded-[1.5rem] blur-md transition-opacity duration-300 opacity-0 group-focus-within:opacity-100"></div>
            <x-mary-input
                wire:model.live.debounce.300ms="search"
                placeholder="Search assignments..."
                icon="o-magnifying-glass"
                clearable
                class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 transition-all duration-300 bg-base-200/50 focus:bg-base-100 h-14 relative z-10"
            />
        </div>
        <div class="flex gap-4 w-full lg:w-auto">
            <x-mary-select
                wire:model.live="filters.status"
                placeholder="Status"
                :options="['draft' => 'Draft', 'published' => 'Published', 'closed' => 'Closed']"
                class="rounded-[1.5rem] border-base-content/5 bg-base-200/50 h-14 min-w-[160px]"
            />
            <x-mary-select
                wire:model.live="filters.is_mandatory"
                placeholder="Mandatory"
                :options="['yes' => 'Mandatory', 'no' => 'Optional']"
                class="rounded-[1.5rem] border-base-content/5 bg-base-200/50 h-14 min-w-[160px]"
            />
            <x-mary-select
                wire:model.live="filters.type_id"
                placeholder="Type"
                :options="$this->assignmentTypes->pluck('name', 'id')"
                class="rounded-[1.5rem] border-base-content/5 bg-base-200/50 h-14 min-w-[160px]"
            />
        </div>
    </div>

    {{-- Selection Bar --}}
    @if($this->selected_count > 0)
        <div class="mb-8 p-4 bg-primary/5 border border-primary/20 rounded-[2rem] flex flex-col sm:flex-row items-center justify-between gap-6 animate-in fade-in slide-in-from-top-4 duration-500 shadow-xl shadow-primary/5 backdrop-blur-md">
            <div class="flex items-center gap-5 pl-2">
                <div class="size-12 rounded-[1.5rem] bg-primary text-primary-content flex items-center justify-center font-black shadow-lg shadow-primary/30 text-lg">
                    {{ $this->selected_count }}
                </div>
                <div class="text-center sm:text-left">
                    <h4 class="font-black text-sm text-primary uppercase tracking-tight">Records Selected</h4>
                    <p class="text-[9px] uppercase font-black tracking-[0.3em] opacity-50 mt-1">Apply bulk operations</p>
                </div>
            </div>
            <div class="flex items-center gap-4 pr-2">
                <div class="flex gap-2">
                    <x-mary-button
                        label="Delete Selected"
                        icon="o-trash"
                        class="btn-error text-white font-black uppercase tracking-widest text-[10px] rounded-xl h-10 px-6 shadow-lg shadow-error/20 hover:scale-105 transition-transform"
                        wire:click="askDeleteSelected"
                    />
                </div>
                <div class="w-px h-8 bg-primary/20 mx-2"></div>
                <x-mary-button
                    label="Cancel"
                    wire:click="clearSelection"
                    class="btn-ghost rounded-xl font-black uppercase tracking-widest text-[10px] hover:bg-base-content/5"
                />
            </div>
        </div>
    @endif

    {{-- Table Section --}}
    <x-mary-card shadow class="card-enterprise !bg-base-100 shadow-2xl shadow-base-content/5 border border-base-content/5 overflow-hidden">
        <div class="table-enterprise overflow-x-auto">
            <x-mary-table
                :headers="$this->headers()"
                :rows="$this->rows()"
                :sort-by="$sortBy"
                with-pagination
                selectable
                wire:model="selectedIds"
                class="table-md w-full whitespace-nowrap"
            >
                @scope('cell_title', $assignment)
                    <div class="flex flex-col py-2">
                        <span class="font-black text-sm tracking-tight text-base-content">{{ $assignment->title }}</span>
                        @if($assignment->description)
                            <span class="text-[10px] opacity-50 mt-0.5 line-clamp-1 max-w-xs">{{ $assignment->description }}</span>
                        @endif
                    </div>
                @endscope

                @scope('cell_is_mandatory', $assignment)
                    @if($assignment->is_mandatory)
                        <span class="badge badge-sm badge-soft badge-error font-black uppercase tracking-wider text-[9px] px-3 py-2 rounded-xl">Required</span>
                    @else
                        <span class="badge badge-sm badge-soft badge-ghost font-black uppercase tracking-wider text-[9px] px-3 py-2 rounded-xl">Optional</span>
                    @endif
                @endscope

                @scope('cell_status', $assignment)
                    @php
                        $badgeClass = match($assignment->status->value) {
                            'draft' => 'badge-ghost',
                            'published' => 'badge-success',
                            'closed' => 'badge-error',
                            default => 'badge-ghost',
                        };
                    @endphp
                    <span class="badge badge-sm {{ $badgeClass }} font-black uppercase tracking-wider text-[9px] px-3 py-2 rounded-xl">
                        {{ $assignment->status->label() }}
                    </span>
                @endscope

                @scope('cell_due_date', $assignment)
                    <span class="font-medium text-sm">
                        {{ $assignment->due_date?->format('d M Y') ?? '—' }}
                    </span>
                @endscope

                @scope('actions', $assignment)
                    <div class="flex items-center justify-end gap-1 py-2">
                        @if($assignment->status->value === 'draft')
                            <x-mary-button icon="o-paper-airplane" class="btn-ghost btn-sm btn-circle text-success hover:bg-success/10 transition-colors" wire:click="publish('{{ $assignment->id }}')" tooltip="Publish" />
                        @endif
                        <x-mary-button icon="o-pencil" class="btn-ghost btn-sm btn-circle text-primary hover:bg-primary/10 transition-colors" wire:click="edit('{{ $assignment->id }}')" tooltip="Edit" />
                        <x-mary-button icon="o-trash" class="btn-ghost btn-sm btn-circle text-error hover:bg-error/10 transition-colors" wire:click="askDelete('{{ $assignment->id }}')" tooltip="Delete" />
                    </div>
                @endscope
            </x-mary-table>
        </div>
    </x-mary-card>

    {{-- Assignment Modal --}}
    <x-mary-modal wire:model="assignmentModal" :title="$formData['id'] ? 'Edit Assignment' : 'Create Assignment'" class="backdrop-blur-sm" box-class="rounded-[2.5rem] p-6 border border-base-content/5 shadow-2xl">
        <div class="grid grid-cols-1 gap-6 pt-4">
            <x-mary-input label="Title" wire:model="formData.title" icon="o-document-text" class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 bg-base-200/50 py-3" />

            <x-mary-select
                label="Type"
                wire:model="formData.assignment_type_id"
                :options="$this->assignmentTypes->pluck('name', 'id')"
                placeholder="Select type"
                class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 bg-base-200/50"
            />

            <x-mary-select
                label="Internship"
                wire:model="formData.internship_id"
                :options="$this->internships->pluck('name', 'id')"
                placeholder="Select internship"
                class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 bg-base-200/50"
            />

            <x-mary-input label="Due Date" type="date" wire:model="formData.due_date" icon="o-calendar" class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 bg-base-200/50 py-3" />

            <x-mary-textarea label="Description" wire:model="formData.description" rows="3" class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 bg-base-200/50" />

            <x-mary-toggle label="Mandatory assignment" wire:model="formData.is_mandatory" class="rounded-xl" />
        </div>

        <x-slot:actions>
            <div class="flex gap-4 pt-6 border-t border-base-content/5 w-full justify-end">
                <x-mary-button label="Cancel" wire:click="$set('assignmentModal', false)" class="btn-ghost rounded-[1.5rem] font-black uppercase tracking-widest text-[10px] px-8" />
                <x-mary-button label="Save" type="submit" class="btn-primary rounded-[1.5rem] font-black uppercase tracking-[0.2em] text-[10px] px-10 shadow-xl shadow-primary/20" wire:click="save" spinner="save" />
            </div>
        </x-slot:actions>
    </x-mary-modal>

    <x-core::ui.confirm message="Are you sure?" />
</div>
