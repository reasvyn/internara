<div class="flex items-center">
    <div class="relative group">
        <div class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-base-content/50 group-focus-within:text-accent transition-colors">
            <x-ui::icon name="tabler.world" class="size-4" />
        </div>
        
        <select 
            wire:change="changeLocale($event.target.value)"
            class="appearance-none bg-transparent h-8 pl-9 pr-8 font-medium text-xs focus:bg-base-200/50 focus:outline-none border-none transition-all hover:bg-base-200/30 rounded-lg cursor-pointer"
            aria-label="{{ __('ui::common.language') }}"
        >
            @foreach($this->locales as $code => $data)
                <option value="{{ $code }}" @selected(App::getLocale() === $code)>
                    {{ $data['name'] }}
                </option>
            @endforeach
        </select>

        <div class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-base-content/30 group-focus-within:text-accent transition-colors">
            <x-ui::icon name="tabler.chevron-down" class="size-3" />
        </div>
    </div>
</div>