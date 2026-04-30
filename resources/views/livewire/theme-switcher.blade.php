<div>
    <x-mary-dropdown>
        <x-slot:trigger>
            <x-mary-button 
                :icon="$themes[$currentTheme]['icon']" 
                class="btn-ghost btn-sm" 
                title="{{ __('theme.switch') }}"
            >
                {{ $themes[$currentTheme]['label'] }}
            </x-mary-button>
        </x-slot:trigger>
        
        @foreach($themes as $key => $theme)
            <x-mary-menu-item 
                :title="$theme['label']" 
                :icon="$theme['icon']" 
                @click="switchTheme('{{ $key }}')"
                :class="$currentTheme === $key ? 'bg-primary text-white' : ''"
            />
        @endforeach
    </x-mary-dropdown>
</div>
