<div class="space-y-6">
    {{-- Header --}}
    <div class="bg-white rounded-lg border border-gray-200 p-6 flex justify-between items-center">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Manajemen Pembatasan Akun</h3>
            <p class="text-gray-600 text-sm mt-1">{{ $totalActive }} pembatasan aktif</p>
        </div>
        <button type="button"
            wire:click="$toggle('showAddRestriction')"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Tambah Pembatasan
        </button>
    </div>

    {{-- Add Restriction Form --}}
    @if ($showAddRestriction)
        <div class="bg-white rounded-lg border border-gray-200 p-6 space-y-4">
            <h4 class="font-semibold text-gray-900">Pembatasan Baru</h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Type --}}
                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-2">Tipe Pembatasan</label>
                    <select wire:model="restrictionType"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Pilih tipe...</option>
                        @foreach ($restrictionTypes as $type)
                            <option value="{{ $type['value'] }}">
                                {{ $type['label'] }} - {{ $type['description'] }}
                            </option>
                        @endforeach
                    </select>
                    @error('restrictionType') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Key --}}
                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-2">Kunci Pembatasan</label>
                    <div class="relative">
                        <input type="text"
                            wire:model="restrictionKey"
                            placeholder="Masukkan kunci..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    @if ($keyExamples)
                        <p class="text-xs text-gray-500 mt-1">
                            Contoh: {{ implode(', ', $keyExamples) }}
                        </p>
                    @endif
                    @error('restrictionKey') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-900 mb-2">Nilai (Opsional)</label>
                <input type="text"
                    wire:model="restrictionValue"
                    placeholder="Misalnya: 5 permintaan/jam"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                @error('restrictionValue') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-900 mb-2">Alasan</label>
                <textarea wire:model="reason"
                    rows="2"
                    placeholder="Jelaskan mengapa pembatasan ini diterapkan..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </textarea>
                @error('reason') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Expiration Date --}}
                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-2">Tanggal Kadaluarsa (Opsional)</label>
                    <input type="datetime-local"
                        wire:model="expiresAt"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    @error('expiresAt') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Auto-lift --}}
                <div class="flex items-end">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox"
                            wire:model="autoLift"
                            class="rounded border-gray-300">
                        <span class="text-sm font-medium text-gray-900">Angkat otomatis setelah kadaluarsa</span>
                    </label>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex gap-3 pt-4 border-t">
                <button type="button"
                    wire:click="addRestriction"
                    class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    Terapkan Pembatasan
                </button>
                <button type="button"
                    wire:click="$toggle('showAddRestriction')"
                    class="flex-1 px-4 py-2 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                    Batal
                </button>
            </div>
        </div>
    @endif

    {{-- Active Restrictions --}}
    <div>
        <h4 class="font-semibold text-gray-900 mb-4">Pembatasan Aktif</h4>

        @forelse ($activeRestrictions as $restriction)
            <div class="bg-white rounded-lg border border-yellow-200 bg-yellow-50 p-4 mb-3 flex justify-between items-start">
                <div>
                    <p class="font-medium text-gray-900">{{ $restriction->restriction_key }}</p>
                    <p class="text-sm text-gray-600 mt-1">
                        Tipe: <span class="font-mono text-xs bg-gray-200 px-2 py-1 rounded">{{ $restriction->restriction_type }}</span>
                    </p>
                    @if ($restriction->restriction_value)
                        <p class="text-sm text-gray-600">Nilai: {{ $restriction->restriction_value }}</p>
                    @endif
                    @if ($restriction->reason)
                        <p class="text-sm text-gray-600 mt-2">Alasan: {{ $restriction->reason }}</p>
                    @endif
                    @if ($restriction->expires_at)
                        <p class="text-xs text-orange-600 mt-2">
                            ⏰ Kadaluarsa: {{ $restriction->expires_at->format('Y-m-d H:i') }}
                        </p>
                    @endif
                </div>
                <button type="button"
                    wire:click="removeRestriction('{{ $restriction->id }}')"
                    class="px-3 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700 transition-colors">
                    Hapus
                </button>
            </div>
        @empty
            <div class="bg-gray-50 rounded-lg border border-gray-200 p-4 text-center text-gray-600">
                Tidak ada pembatasan aktif
            </div>
        @endforelse

        @if ($activeRestrictions->hasPages())
            <div class="mt-4">
                {{ $activeRestrictions->links() }}
            </div>
        @endif
    </div>

    {{-- Inactive/Expired Restrictions --}}
    @if ($inactiveRestrictions->count() > 0)
        <div class="border-t pt-6">
            <h4 class="font-semibold text-gray-900 mb-4">Pembatasan Tidak Aktif</h4>

            @forelse ($inactiveRestrictions as $restriction)
                <div class="bg-gray-50 rounded-lg border border-gray-200 p-4 mb-3 opacity-75">
                    <p class="font-medium text-gray-600">{{ $restriction->restriction_key }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        @if ($restriction->expires_at && $restriction->expires_at < now())
                            Kadaluarsa: {{ $restriction->expires_at->format('Y-m-d H:i') }}
                        @else
                            Dihapus
                        @endif
                    </p>
                </div>
            @empty
            @endforelse

            @if ($inactiveRestrictions->hasPages())
                <div class="mt-4">
                    {{ $inactiveRestrictions->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
