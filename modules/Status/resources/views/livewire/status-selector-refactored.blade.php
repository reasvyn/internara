<div class="space-y-6">
    {{-- Current Status Display --}}
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-start justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Status Akun Saat Ini</h3>
                <div class="flex items-center gap-3">
                    <div class="px-4 py-2 rounded-full text-sm font-medium text-white" style="background-color: {{ $currentStatus['color'] }};">
                        {{ $currentStatus['label'] }}
                    </div>
                </div>
                <p class="text-gray-600 text-sm mt-3">{{ $currentStatus['description'] }}</p>
            </div>
            @if ($currentStatus['isProtected'])
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-3">
                    <p class="text-sm font-medium text-purple-900">🔒 Terlindungi</p>
                    <p class="text-xs text-purple-700 mt-1">Tidak dapat diubah</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Status Transition Form --}}
    @if ($canManage && !$currentStatus['isProtected'])
        <button
            type="button"
            wire:click="$toggle('showTransitionForm')"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium"
        >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            Ubah Status
        </button>

        @if ($showTransitionForm)
            <div class="bg-white rounded-lg border border-gray-200 p-6 space-y-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Ubah Status Akun</h3>
                    <p class="text-sm text-gray-600">Pilih status baru untuk {{ $user->name }} ({{ $userRole }})</p>
                </div>

                {{-- Error Message --}}
                @if ($errorMessage)
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 flex gap-3">
                        <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        <div>
                            <p class="font-medium text-red-900">Kesalahan</p>
                            <p class="text-sm text-red-700 mt-1">{{ $errorMessage }}</p>
                        </div>
                    </div>
                @endif

                {{-- Status Radio Options --}}
                <div class="space-y-3">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Pilih Status Baru</label>

                    @forelse ($availableTransitions as $transition)
                        <label class="flex items-start p-4 border-2 rounded-lg cursor-pointer transition-all"
                            :class="{ 
                                'border-blue-500 bg-blue-50': selectedStatus === '{{ $transition['value'] }}',
                                'border-gray-200 hover:border-gray-300': selectedStatus !== '{{ $transition['value'] }}',
                                'opacity-50 cursor-not-allowed bg-gray-50': !{{ $transition['canSelect'] ? 'true' : 'false' }}
                            }}"
                        >
                            <input
                                type="radio"
                                wire:model="selectedStatus"
                                value="{{ $transition['value'] }}"
                                :disabled="!{{ $transition['canSelect'] ? 'true' : 'false' }}"
                                class="mt-1 mr-4 flex-shrink-0"
                            >

                            <div class="flex-1">
                                {{-- Status Badge --}}
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium text-white"
                                        style="background-color: {{ $transition['color'] }};">
                                        {{ $transition['label'] }}
                                    </span>
                                    @if (!$transition['canSelect'])
                                        <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 0 1 5.11 2.697m13.254 2.939l-12.02 12.02" stroke-width="2" />
                                        </svg>
                                    @endif
                                </div>

                                {{-- Description --}}
                                <p class="text-gray-700 text-sm mb-2">{{ $transition['description'] }}</p>

                                {{-- Block Reason if Disabled --}}
                                @if (!$transition['canSelect'] && $transition['blockReason'])
                                    <p class="text-red-600 text-xs">⛔ {{ $transition['blockReason'] }}</p>
                                @endif
                            </div>
                        </label>
                    @empty
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                            <p class="text-yellow-900 font-medium">Tidak ada transisi yang tersedia</p>
                            <p class="text-yellow-800 text-sm mt-1">Status saat ini tidak memiliki transisi yang valid untuk role Anda</p>
                        </div>
                    @endforelse
                </div>

                {{-- Admin Notes --}}
                <div>
                    <label for="adminNotes" class="block text-sm font-medium text-gray-900 mb-2">
                        Alasan Perubahan Status (Opsional)
                    </label>
                    <textarea
                        id="adminNotes"
                        wire:model="adminNotes"
                        rows="3"
                        placeholder="Jelaskan mengapa status ini diubah (mis: berdasarkan investigasi, permintaan user, dll)..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                        :disabled="$isLoading"
                    ></textarea>
                    <p class="text-gray-500 text-xs mt-1">{{ strlen($adminNotes) }}/500 karakter</p>
                </div>

                {{-- Action Buttons --}}
                <div class="flex gap-3 pt-4 border-t border-gray-200">
                    <button
                        type="button"
                        wire:click="transitionStatus"
                        :disabled="$isLoading"
                        class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
                    >
                        @if ($isLoading)
                            <span class="flex items-center justify-center gap-2">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Memproses...
                            </span>
                        @else
                            ✅ Terapkan Perubahan
                        @endif
                    </button>
                    <button
                        type="button"
                        wire:click="$toggle('showTransitionForm')"
                        :disabled="$isLoading"
                        class="flex-1 px-4 py-2 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300 disabled:opacity-50 transition-colors font-medium"
                    >
                        Batal
                    </button>
                </div>
            </div>
        @endif
    @elseif ($currentStatus['isProtected'])
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 flex gap-3">
            <svg class="w-5 h-5 text-purple-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
            </svg>
            <div>
                <p class="font-medium text-purple-900">Status Terlindungi</p>
                <p class="text-sm text-purple-800">Akun Super Admin tidak dapat diubah statusnya. Status ini bersifat permanen dan terlindungi.</p>
            </div>
        </div>
    @else
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 flex gap-3">
            <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <div>
                <p class="font-medium text-yellow-900">Tidak Ada Akses</p>
                <p class="text-sm text-yellow-800">Anda tidak memiliki izin untuk mengubah status akun ini. Hubungi admin untuk bantuan lebih lanjut.</p>
            </div>
        </div>
    @endif
</div>
