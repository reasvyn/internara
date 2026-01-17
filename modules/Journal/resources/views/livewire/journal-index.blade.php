<div>
    <x-ui::header title="{{ __('Jurnal Harian') }}" subtitle="{{ __('Catat dan pantau aktivitas magang setiap hari.') }}">
        <x-slot:actions>
            @can('create', \Modules\Journal\Models\JournalEntry::class)
                <x-ui::button label="{{ __('Buat Jurnal Baru') }}" icon="o-plus" class="btn-primary" link="{{ route('journal.create') }}" />
            @endcan
        </x-slot:actions>
    </x-ui::header>

    <x-ui::main>
        <x-ui::card>
            <div class="mb-4">
                <x-ui::input placeholder="{{ __('Cari topik atau kompetensi...') }}" icon="o-magnifying-glass" wire:model.live.debounce.300ms="search" clearable />
            </div>

            <x-ui::table :headers="[
                ['key' => 'date', 'label' => __('Tanggal')],
                ['key' => 'student.name', 'label' => __('Siswa')],
                ['key' => 'work_topic', 'label' => __('Topik Pekerjaan')],
                ['key' => 'status', 'label' => __('Status')],
            ]" :rows="$this->journals">
                @scope('cell_date', $entry)
                    {{ $entry->date->format('d/m/Y') }}
                @endscope

                @scope('cell_status', $entry)
                    <x-ui::badge :label="$entry->getStatusLabel()" :class="'badge-' . $entry->getStatusColor()" />
                @endscope

                @scope('actions', $entry)
                    <div class="flex gap-2">
                        <x-ui::button icon="o-eye" class="btn-ghost btn-sm text-info" tooltip="{{ __('Lihat Detail') }}" link="#" />
                        
                        @can('update', $entry)
                            <x-ui::button icon="o-pencil" class="btn-ghost btn-sm text-warning" tooltip="{{ __('Edit') }}" link="#" />
                        @endcan

                        @can('validate', $entry)
                            @if($entry->latestStatus()?->name !== 'approved')
                                <x-ui::button icon="o-check" class="btn-ghost btn-sm text-success" tooltip="{{ __('Setujui') }}" wire:click="approve('{{ $entry->id }}')" />
                            @endif
                        @endcan
                    </div>
                @endscope
            </x-ui::table>
        </x-ui::card>
    </x-ui::main>
</div>