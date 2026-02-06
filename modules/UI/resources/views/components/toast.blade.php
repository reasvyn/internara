<div 
    x-data="{
        toasts: [],
        add(payload) {
            const id = Date.now();
            const toast = {
                id,
                message: payload.message || payload.description,
                type: payload.type || 'info',
                title: payload.title || (payload.type ? payload.type.charAt(0).toUpperCase() + payload.type.slice(1) : 'Notification'),
                visible: false
            };

            this.toasts.push(toast);

            setTimeout(() => {
                const index = this.toasts.findIndex(t => t.id === id);
                if (index !== -1) this.toasts[index].visible = true;
            }, 100);

            setTimeout(() => this.remove(id), payload.timeout || 5000);
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
    class="toast toast-end toast-bottom z-[100] gap-3 p-6 pointer-events-none"
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
            class="alert shadow-2xl border border-base-content/5 flex items-start gap-4 min-w-[22rem] max-w-md pointer-events-auto cursor-pointer transition-all hover:scale-[1.02] active:scale-[0.98] rounded-2xl p-4"
            :class="{
                'bg-success text-success-content': toast.type === 'success',
                'bg-error text-error-content': toast.type === 'error',
                'bg-warning text-warning-content': toast.type === 'warning',
                'bg-info text-info-content': toast.type === 'info',
            }"
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

            <button class="btn btn-ghost btn-circle btn-xs -mr-1 opacity-50 hover:opacity-100" @click.stop="remove(toast.id)">
                <x-ui::icon name="tabler.x" class="size-3" />
            </button>
        </div>
    </template>
</div>