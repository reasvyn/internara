<div>
    <x-ui::dropdown>
        <x-slot:trigger>
            <div class="flex items-center gap-2" role="button" tabindex="0" aria-label="{{ __('ui::common.language') }}">
                @if(isset($this->locales[App::getLocale()]))
                    <x-mary-icon :name="$this->locales[App::getLocale()]['icon']" class="w-5 h-5" />
                @else
                    <x-mary-icon name="tabler.world" class="w-5 h-5" />
                @endif
                <span class="hidden md:inline">{{ $this->locales[App::getLocale()]['name'] ?? __('ui::common.language') }}</span>
            </div>
        </x-slot:trigger>

        @foreach($this->locales as $code => $data)
            <x-ui::menu-item 
                wire:click="changeLocale('{{ $code }}')" 
                @class(['bg-base-200' => App::getLocale() === $code])
                aria-label="{{ $data['name'] }}"
            >
                <x-slot:title>
                    <div class="flex items-center gap-2">
                        <x-mary-icon :name="$data['icon']" class="w-4 h-4" />
                        <span>{{ $data['name'] }}</span>
                    </div>
                </x-slot:title>
            </x-ui::menu-item>
        @endforeach
    </x-ui::dropdown>
</div>