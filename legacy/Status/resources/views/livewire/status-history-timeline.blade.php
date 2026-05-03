<div class="space-y-6">
    {{-- Header with Filters and Export --}}
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Riwayat Status Akun</h3>
            <button type="button"
                wire:click="exportCsv"
                class="inline-flex items-center px-3 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-4m0 0V8m0 4H8m4 0h4" />
                </svg>
                Ekspor CSV
            </button>
        </div>

        <p class="text-gray-600 text-sm mb-4">Total {{ $totalRecords }} perubahan status</p>

        {{-- Filters --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-900 mb-2">Filter Status</label>
                <select wire:model="filterStatus"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Status</option>
                    @foreach ($statusOptions as $option)
                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-900 mb-2">Diubah Oleh</label>
                <select wire:model="filterBy"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Admin</option>
                    @foreach ($adminOptions as $option)
                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <button type="button"
                    wire:click="clearFilters"
                    class="w-full px-3 py-2 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                    Reset Filter
                </button>
            </div>
        </div>
    </div>

    {{-- Timeline --}}
    <div class="space-y-4">
        @forelse ($history as $record)
            <div class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-md transition-shadow">
                <div class="flex items-start gap-4">
                    {{-- Timeline Dot --}}
                    <div class="shrink-0 mt-1">
                        <div class="w-3 h-3 bg-blue-600 rounded-full"></div>
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-mono text-xs text-gray-500">
                                {{ $record->created_at->format('Y-m-d H:i:s') }}
                            </span>
                            <span class="text-gray-400">•</span>
                            <span class="text-sm font-medium text-gray-900">
                                {{ $record->triggeredBy?->name ?? 'System' }}
                            </span>
                            @if ($record->triggered_by_role)
                                <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded">
                                    {{ $record->triggered_by_role }}
                                </span>
                            @endif
                        </div>

                        {{-- Status Transition --}}
                        <div class="mt-3 flex items-center gap-3">
                            @if ($record->old_status)
                                <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded">
                                    {{ $record->old_status }}
                                </span>
                                <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            @else
                                <span class="text-xs text-gray-500 italic">Initial</span>
                                <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            @endif
                            <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 rounded">
                                {{ $record->new_status }}
                            </span>
                        </div>

                        {{-- Reason and IP --}}
                        @if ($record->reason)
                            <p class="text-sm text-gray-600 mt-2">{{ $record->reason }}</p>
                        @endif

                        <div class="flex gap-4 mt-3 text-xs text-gray-500">
                            @if ($record->ip_address)
                                <div class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ $record->ip_address }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-gray-50 rounded-lg border border-gray-200 p-8 text-center">
                <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-gray-600">Tidak ada perubahan status</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if ($history->hasPages())
        <div class="mt-6">
            {{ $history->links() }}
        </div>
    @endif
</div>
