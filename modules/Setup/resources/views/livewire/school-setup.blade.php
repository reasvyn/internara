<x-setup::layouts.setup-wizard>
    <x-slot:header>
        <p class="mb-16 font-bold text-gray-500">Langkah 3 dari 6</p>

        <h1 class="text-3xl font-bold">Membangun Identitas Sekolah Anda.</h1>

        <p class="mt-4">
            Informasi ini akan menjadi fondasi dari seluruh sistem, memastikan setiap dokumen,
            laporan, dan komunikasi membawa identitas unik sekolah Anda. Mari kita perkenalkan
            institusi Anda pada Internara.
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
        @slotRender('school-manager')
    </x-slot>
</x-setup::layouts.setup-wizard>
