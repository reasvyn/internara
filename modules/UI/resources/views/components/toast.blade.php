<div 
    x-data="{
        toasts: [],
        translations: {
            success: '{{ __('ui::common.success') }}',
            error: '{{ __('ui::common.error') }}',
            warning: '{{ __('ui::common.warning') }}',
            info: '{{ __('ui::common.notification') }}'
        },
        add(payload) {
            const id = Date.now();
            const timeout = payload.timeout || 5000;
            const type = payload.type || 'info';
            const message = payload.message || payload.description;
            const autohide = payload.autohide !== undefined ? payload.autohide : true;
            
            // Prevent duplicates in current queue
            if (this.toasts.some(t => t.message === message && t.type === type)) {
                return;
            }

            console.log(`NOTIFY: [${type.toUpperCase()}] ${message}`);

            const toast = {
                id,
                message,
                type,
                title: payload.title || this.translations[type],
                visible: false,
                progress: 100,
                autohide
            };

            this.toasts.push(toast);

            // Trigger enter animation
            setTimeout(() => {
                const index = this.toasts.findIndex(t => t.id === id);
                if (index !== -1) this.toasts[index].visible = true;
            }, 100);

            // Progress bar animation
            if (autohide) {
                const start = Date.now();
                const timer = setInterval(() => {
                    const index = this.toasts.findIndex(t => t.id === id);
                    if (index === -1) {
                        clearInterval(timer);
                        return;
                    }
                    const elapsed = Date.now() - start;
                    this.toasts[index].progress = Math.max(0, 100 - (elapsed / timeout * 100));
                    
                    if (this.toasts[index].progress <= 0) {
                        clearInterval(timer);
                    }
                }, 50);

                setTimeout(() => this.remove(id), timeout);
            } else {
                // Keep progress bar full for non-autohide
                const index = this.toasts.findIndex(t => t.id === id);
                if (index !== -1) this.toasts[index].progress = 100;
            }
        },
        remove(id) {
            const index = this.toasts.findIndex(t => t.id === id);
            if (index !== -1) {
                this.toasts[index].visible = false;
                setTimeout(() => {
                    this.toasts = this.toasts.filter(t => t.id !== id);
                }, 400);
            }
        }
    }"
    @notify.window="add($event.detail)"
    class="toast toast-end toast-bottom z-50 gap-3 p-6 pointer-events-none"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div 
            x-show="toast.visible"
            x-transition:enter="transition ease-out duration-500"
            x-transition:enter-start="translate-x-full opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="translate-x-full opacity-0"
            class="alert relative overflow-hidden shadow-2xl border border-base-content/5 flex items-start gap-4 min-w-[22rem] max-w-md pointer-events-auto cursor-pointer transition-all hover:scale-[1.02] active:scale-[0.98] rounded-2xl p-4"
            :class="{
                'bg-success text-success-content': toast.type === 'success',
                'bg-error text-error-content': toast.type === 'error',
                'bg-warning text-warning-content': toast.type === 'warning',
                'bg-info text-info-content': toast.type === 'info',
            }"
            role="status"
            aria-live="polite"
            @click="remove(toast.id)"
        >
            <div class="mt-0.5 bg-base-100/20 p-2 rounded-xl">
                <template x-if="toast.type === 'success'"><x-ui::icon name="tabler.check" class="size-5" /></template>
                <template x-if="toast.type === 'error'"><x-ui::icon name="tabler.alert-circle" class="size-5" /></template>
                <template x-if="toast.type === 'warning'"><x-ui::icon name="tabler.alert-triangle" class="size-5" /></template>
                <template x-if="toast.type === 'info'"><x-ui::icon name="tabler.info-circle" class="size-5" /></template>
            </div>
            
            <div class="flex-1 pt-1">
                <h4 class="font-black text-sm uppercase tracking-widest opacity-90" x-text="toast.title"></h4>
                <p class="text-xs font-bold leading-relaxed opacity-100 mt-0.5" x-text="toast.message"></p>
            </div>

            <button 
                class="btn btn-ghost btn-circle btn-xs -mr-1 opacity-50 hover:opacity-100" 
                @click.stop="remove(toast.id)"
                aria-label="{{ __('ui::common.close') }}"
            >
                <x-ui::icon name="tabler.x" class="size-3" />
            </button>

            {{-- Time Bar (Progress) --}}
            <div 
                class="absolute bottom-0 left-0 h-1 bg-white/40 transition-all duration-75 ease-linear" 
                :style="`width: ${toast.progress}%`"
            ></div>
        </div>
    </template>
</div>
