<div class="space-y-6">
    {{-- Current Status Display --}}
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Status Akun Saat Ini</h3>
        
        <div class="flex items-center gap-4">
            <div class="flex-1">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                        style="background-color: {{ $currentStatus['color'] }}20; color: {{ $currentStatus['color'] }};">
                        {{ $currentStatus['label'] }}
                    </span>
                </div>
                <p class="text-gray-600 text-sm mt-2">{{ $currentStatus['description'] }}</p>
            </div>
            
            @if ($daysUntilInactive > 0)
                <div class="text-right">
                    <p class="text-sm text-gray-500">Hari hingga otomatis tidak aktif</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $daysUntilInactive }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Transition Form --}}
    @if (!$user->isProtected())
        <button type="button" 
            wire:click="$toggle('showTransitionForm')"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Ubah Status Akun
        </button>

        @if ($showTransitionForm)
            <div class="bg-white rounded-lg border border-gray-200 p-6 space-y-6">
                <h3 class="text-lg font-semibold text-gray-900">Pilih Status Baru</h3>

                {{-- Status Radio Options --}}
                <div class="space-y-3">
                    @foreach ($availableTransitions as $transition)
                        <label class="flex items-start p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50"
                            :class="{ 'border-blue-500 bg-blue-50': selectedStatus === '{{ $transition['status']->value }}' }">
                            <input type="radio" 
                                wire:model="selectedStatus" 
                                value="{{ $transition['status']->value }}"
                                :disabled="!{{ $transition['canTransition'] ? 'true' : 'false' }}"
                                class="mt-1 mr-4">
                            
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-1 rounded text-sm font-medium"
                                        style="background-color: {{ $transition['color'] }}20; color: {{ $transition['color'] }};">
                                        {{ $transition['label'] }}
                                    </span>
                                    @if (!$transition['canTransition'])
                                        <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 0 1 5.11 2.697M18.364 5.636l-12.02 12.02" />
                                        </svg>
                                    @endif
                                </div>
                                <p class="text-gray-600 text-sm mt-1">{{ $transition['description'] }}</p>
                                @if (!$transition['canTransition'])
                                    <p class="text-red-600 text-xs mt-2">{{ $transition['reason'] }}</p>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>

                {{-- Admin Notes --}}
                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-2">
                        Catatan Admin (Alasan Perubahan)
                    </label>
                    <textarea wire:model="adminNotes"
                        rows="4"
                        placeholder="Jelaskan mengapa status ini diubah..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </textarea>
                    <p class="text-gray-500 text-xs mt-1">{{ strlen($adminNotes ?? '') }}/500 karakter</p>
                </div>

                {{-- Action Buttons --}}
                <div class="flex gap-3">
                    <button type="button"
                        wire:click="transitionStatus"
                        class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        Terapkan Perubahan
                    </button>
                    <button type="button"
                        wire:click="$toggle('showTransitionForm')"
                        class="flex-1 px-4 py-2 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                        Batal
                    </button>
                </div>
            </div>
        @endif
    @else
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 flex gap-3">
            <svg class="w-5 h-5 text-yellow-600 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <div>
                <p class="font-medium text-yellow-900">Status Terlindungi</p>
                <p class="text-sm text-yellow-800">Akun Super Admin tidak dapat diubah statusnya</p>
            </div>
        </div>
    @endif
</div>
