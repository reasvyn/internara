<x-ui::record-manager>
    {{-- Custom Table Cells --}}
    <x-slot:tableCells>
        @scope('cell_created_at', $department)
            <span class="text-xs opacity-70">{{ \Carbon\Carbon::parse($department->created_at)->translatedFormat('d M Y H:i') }}</span>
        @endscope
    </x-slot:tableCells>

    {{-- Form Fields --}}
    <x-slot:formFields>
        <x-ui::input 
            :label="__('department::ui.name')" 
            icon="tabler.category" 
            wire:model="form.name" 
            required 
        />
        <x-ui::textarea 
            :label="__('ui::common.description')" 
            icon="tabler.align-left" 
            wire:model="form.description" 
        />
    </x-slot:formFields>
</x-ui::record-manager>