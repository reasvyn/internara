<div>
    <x-mary-dropdown>
        <x-slot:trigger>
            <x-mary-button 
                icon="o-language" 
                class="btn-ghost btn-sm" 
                title="{{ __('language.switch') }}"
            >
                {{ $locales[$currentLocale] ?? 'EN' }}
            </x-mary-button>
        </x-slot:trigger>
        
        <x-mary-menu-item 
            title="English" 
            icon="o-flag-en" 
            @click="switchLanguage('en')"
            :class="$currentLocale === 'en' ? 'bg-primary text-white' : ''"
        />
        <x-mary-menu-item 
            title="Indonesia" 
            icon="o-flag-id" 
            @click="switchLanguage('id')"
            :class="$currentLocale === 'id' ? 'bg-primary text-white' : ''"
        />
    </x-mary-dropdown>
</div>
