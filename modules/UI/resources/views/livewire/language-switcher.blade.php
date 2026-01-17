<div>
    <x-mary-dropdown>
        <x-slot:label>
            <div class="flex items-center gap-2">
                <span>{{ $locales[App::getLocale()]['flag'] ?? '🌐' }}</span>
                <span class="hidden md:inline">{{ $locales[App::getLocale()]['name'] ?? 'Language' }}</span>
            </div>
        </x-slot:label>

        @foreach($locales as $code => $data)
            <x-mary-menu-item 
                title="{{ $data['flag'] }} {{ $data['name'] }}" 
                wire:click="changeLocale('{{ $code }}')" 
                @class(['bg-base-200' => App::getLocale() === $code])
            />
        @endforeach
    </x-mary-dropdown>
</div>