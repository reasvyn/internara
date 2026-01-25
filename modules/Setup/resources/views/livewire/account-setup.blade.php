<x-setup::layouts.setup-wizard>
    <x-slot:header>
        <p class="mb-16 font-bold text-gray-500">Langkah 2 dari 6</p>

        <h1 class="text-3xl font-bold">Setiap Perjalanan Hebat Butuh Seorang Pemimpin.</h1>

        <p class="mt-4">
            Akun ini akan menjadi pusat kendali Anda. Dengan akun inilah Anda akan mengarahkan alur
            program magang, mengelola pengguna, dan memastikan semuanya berjalan lancar. Mari kita
            siapkan akun administrator utama Anda.
        </p>

        <div class="mt-8 flex items-center gap-4">
            <x-ui::button class="btn-secondary btn-outline" label="Kembali" wire:click="backToPrev" />
            <x-ui::button
                class="btn-primary"
                label="Lanjutkan"
                wire:click="nextStep"
                :disabled="$this->disableNextStep"
            />
        </div>
    </x-slot>

    <x-slot:content>
        @slotRender('register.super-admin')
    </x-slot>
</x-setup::layouts.setup-wizard>
