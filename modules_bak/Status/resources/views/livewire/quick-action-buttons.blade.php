<div class="flex gap-2 flex-wrap">
    @forelse ($availableActions as $action)
        <button type="button"
            wire:click="executeAction('{{ $action['id'] }}')"
            class="px-3 py-1 text-sm rounded-lg font-medium transition-colors flex items-center gap-1"
            :class="{
                'bg-green-100 text-green-700 hover:bg-green-200': '{{ $action['color'] }}' === 'green',
                'bg-red-100 text-red-700 hover:bg-red-200': '{{ $action['color'] }}' === 'red',
                'bg-blue-100 text-blue-700 hover:bg-blue-200': '{{ $action['color'] }}' === 'blue',
            }">
            @switch($action['icon'])
                @case('check')
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                @break
                @case('ban')
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 0 1 5.11 2.697M18.364 5.636l-12.02 12.02" stroke="currentColor" stroke-width="1.5" />
                    </svg>
                @break
                @case('unlock')
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" />
                    </svg>
                @break
            @endswitch
            {{ $action['label'] }}
        </button>
    @empty
        <p class="text-sm text-gray-500">Tidak ada aksi yang tersedia</p>
    @endforelse
</div>
