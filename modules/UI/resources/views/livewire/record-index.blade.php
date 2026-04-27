<div class="space-y-8">
    {{-- Executive Summary: Premium Stats Grid --}}
    @if(count($this->stats) > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($this->stats as $stat)
                <x-ui::stat 
                    :title="$stat['title']" 
                    :value="$stat['value']" 
                    :icon="$stat['icon']" 
                    :variant="$stat['variant'] ?? 'metadata'" 
                    class="stat-enterprise" 
                    wire:loading.class="opacity-50"
                />
            @endforeach
        </div>
    @endif

    {{-- Core Record Management Interface --}}
    <div class="bg-base-100 rounded-3xl p-8 md:p-12 shadow-sm border border-base-content/5">
        @livewire($managerComponent)
    </div>
</div>
