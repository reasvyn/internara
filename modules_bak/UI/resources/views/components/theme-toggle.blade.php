<div 
    x-data="{ 
        theme: localStorage.getItem('theme') || 'light',
        toggle() {
            this.theme = this.theme === 'light' ? 'dark' : 'light';
            localStorage.setItem('theme', this.theme);
            document.documentElement.setAttribute('data-theme', this.theme);
            this.$dispatch('theme-changed', this.theme);
        }
    }"
    x-init="document.documentElement.setAttribute('data-theme', theme)"
    class="flex items-center"
>
    <button 
        type="button"
        x-on:click="toggle()" 
        {{ $attributes->merge(['class' => 'btn btn-ghost btn-circle btn-sm']) }}
        aria-label="{{ __('ui::common.toggle_theme') }}"
    >
        <x-ui::icon x-show="theme === 'light'" name="tabler.sun" class="size-5" />
        <x-ui::icon x-show="theme === 'dark'" name="tabler.moon" class="size-5" />
    </button>
</div>
