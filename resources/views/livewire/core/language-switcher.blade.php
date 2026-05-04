<div>
    <x-mary-dropdown>
        <x-slot:trigger>
            <x-mary-button 
                icon="o-language" 
                class="btn-ghost btn-sm" 
                :label="strtoupper($currentLocale)"
                title="{{ trans('common.language.switch') ?: 'Switch Language' }}"
            />
        </x-slot:trigger>
        
        @foreach($supportedLocales as $code => $label)
            <x-mary-menu-item 
                :title="$label" 
                icon="o-flag" 
                wire:click="switchLanguage('{{ $code }}')"
                :class="$currentLocale === $code ? 'bg-primary/10 text-primary font-bold' : ''"
            />
        @endforeach
    </x-mary-dropdown>
</div>
