<div class="space-y-8">
    {{-- Executive Summary: Premium Stats Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <x-ui::stat 
            :title="__('internship::ui.stats.total_requirements')" 
            :value="$this->stats['total']" 
            icon="tabler.list-check" 
            variant="metadata" 
            class="stat-enterprise" 
            wire:loading.class="opacity-50"
        />
        <x-ui::stat 
            :title="__('internship::ui.stats.mandatory_requirements')" 
            :value="$this->stats['mandatory']" 
            icon="tabler.alert-circle" 
            variant="error" 
            class="stat-enterprise" 
            wire:loading.class="opacity-50"
        />
        <x-ui::stat 
            :title="__('internship::ui.stats.active_requirements')" 
            :value="$this->stats['active']" 
            icon="tabler.circle-check" 
            variant="success" 
            class="stat-enterprise" 
            wire:loading.class="opacity-50"
        />
        <x-ui::stat 
            :title="__('internship::ui.stats.document_requirements')" 
            :value="$this->stats['documents']" 
            icon="tabler.file-text" 
            variant="info" 
            class="stat-enterprise" 
            wire:loading.class="opacity-50"
        />
    </div>

    <x-ui::record-manager>
        {{-- 1. Customized Table Cells --}}
        <x-slot:tableCells>
            @scope('cell_name', $requirement)
                <div class="flex flex-col min-w-[200px] group">
                    <span class="font-bold text-sm text-base-content/90 group-hover:text-primary transition-colors">{{ $requirement->name }}</span>
                    @if($requirement->description)
                        <span class="text-[10px] opacity-40 uppercase tracking-widest font-black line-clamp-1 italic">{{ $requirement->description }}</span>
                    @endif
                </div>
            @endscope

            @scope('cell_type', $requirement)
                <x-ui::badge 
                    :value="__('internship::ui.' . $requirement->type)" 
                    variant="neutral" 
                    class="badge-sm font-black text-[9px] uppercase tracking-tighter rounded-lg" 
                />
            @endscope

            @scope('cell_is_mandatory', $requirement)
                <div class="flex justify-center">
                    @if($requirement->is_mandatory)
                        <x-ui::badge value="{{ __('internship::ui.mandatory') }}" variant="error" class="badge-xs font-black text-[8px] uppercase" />
                    @else
                        <x-ui::badge value="{{ __('internship::ui.optional') }}" variant="ghost" class="badge-xs font-black text-[8px] uppercase opacity-40" />
                    @endif
                </div>
            @endscope

            @scope('cell_is_active', $requirement)
                <div class="flex justify-center">
                    <div class="size-2 rounded-full {{ $requirement->is_active ? 'bg-success shadow-[0_0_8px_rgba(34,197,94,0.5)]' : 'bg-base-content/20' }}"></div>
                </div>
            @endscope
        </x-slot:tableCells>

            {{-- Row Actions --}}
            <x-slot:rowActions>
                @scope('cell_actions', $requirement)
                    <div class="flex items-center justify-end gap-1 px-2">
                        @if($this->can('update', $requirement))
                            <x-ui::button icon="tabler.edit" variant="tertiary" class="text-info/40 hover:text-info btn-xs" wire:click="edit('{{ $requirement->id }}')" tooltip="{{ __('ui::common.edit') }}" />
                        @endif
                        @if($this->can('delete', $requirement))
                            <x-ui::button icon="tabler.trash" variant="tertiary" class="text-error/40 hover:text-error btn-xs" wire:click="discard('{{ $requirement->id }}')" tooltip="{{ __('ui::common.delete') }}" />
                        @endif
                    </div>
                @endscope
            </x-slot:rowActions>
            {{-- 2. Form Fields --}}
        <x-slot:formFields>
            <x-ui::input :label="__('internship::ui.requirement_name')" icon="tabler.list-details" wire:model="form.name" required />
            <x-ui::textarea :label="__('ui::common.description')" icon="tabler.align-left" wire:model="form.description" />
            
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui::select 
                    :label="__('internship::ui.requirement_type')" 
                    icon="tabler.category"
                    wire:model="form.type" 
                    :options="[
                        ['id' => 'document', 'name' => __('internship::ui.document')],
                        ['id' => 'skill', 'name' => __('internship::ui.skill')],
                        ['id' => 'condition', 'name' => __('internship::ui.condition')],
                    ]" 
                    required 
                />
                <x-ui::input :label="__('internship::ui.academic_year')" icon="tabler.calendar-event" wire:model="form.academic_year" placeholder="YYYY/YYYY" required />
            </div>

            <div class="flex items-center gap-8 py-4 border-t border-base-content/5 mt-4">
                <x-ui::checkbox :label="__('internship::ui.mandatory')" wire:model="form.is_mandatory" />
                <x-ui::checkbox :label="__('internship::ui.active')" wire:model="form.is_active" />
            </div>

            <div wire:loading wire:target="save" class="text-[10px] font-black uppercase tracking-widest text-primary animate-pulse mt-2">
                {{ __('ui::common.saving') }}...
            </div>
        </x-slot:formFields>
    </x-ui::record-manager>
</div>
