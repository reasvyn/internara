<div>
    <x-slot:title>Reports</x-slot:title>

    <x-shared::ui.page-header title="Reports" />

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        @foreach($types as $key => $label)
            <x-mary-card class="hover:shadow-lg transition-shadow">
                <h3 class="font-bold text-sm mb-2">{{ $label }}</h3>
                <p class="text-xs text-base-content/60 mb-4">Generate a {{ strtolower($label) }} in PDF format.</p>
                <x-mary-button wire:click="generateReport('{{ $key }}')" label="Generate" icon="o-document-plus" class="btn-primary btn-sm" />
            </x-mary-card>
        @endforeach
    </div>

    <x-mary-card>
        <h3 class="font-bold text-sm mb-4">Generated Reports</h3>

        <x-mary-table :headers="[['key' => 'name', 'label' => 'Name'], ['key' => 'created_at', 'label' => 'Generated']]" :rows="$reports">
            @scope('actions', $report)
                <div class="flex gap-2">
                    <a href="{{ route('admin.reports.download', $report->id) }}" class="btn btn-sm btn-primary">
                        <x-mary-icon name="o-arrow-down-tray" class="size-4" />
                        Download
                    </a>
                    <x-mary-button wire:click="deleteReport('{{ $report->id }}')" icon="o-trash" class="btn-sm btn-ghost text-error" />
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>
</div>
