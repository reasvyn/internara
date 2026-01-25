<x-setup::layouts.setup-wizard>
    <x-slot:header>
        <p class="mb-16 font-bold text-gray-500">Langkah 5 dari 6</p>

        <h1 class="text-3xl font-bold">Menyiapkan Jalur-Jalur Keahlian.</h1>

        <p class="mt-4">
            Setiap jurusan adalah jalur unik yang akan ditempuh siswa. Dengan mendefinisikan
            jurusan-jurusan ini, kita memudahkan penempatan magang yang sesuai dengan keahlian
            mereka. Masukkan jurusan-jurusan yang ada di sekolah Anda.
        </p>
        <p class="mt-2 text-sm text-base-content/70">
            Anda dapat mengubah pengaturan ini nanti melalui halaman pengaturan.
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
        @slotRender('department-manager')
    </x-slot>
</x-setup::layouts.setup-wizard>
