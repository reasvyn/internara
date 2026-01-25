<x-setup::layouts.setup-wizard>
    <x-slot:header>
        <p class="mb-16 font-bold text-gray-500">Langkah 4 dari 6</p>

        <h1 class="text-3xl font-bold">Pastikan Jalur Komunikasi Terbuka.</h1>

        <p class="mt-4">
            Internara perlu mengirimkan notifikasi penting, laporan, dan konfirmasi akun melalui
            email. Konfigurasikan server SMTP Anda untuk memastikan setiap pesan sampai ke
            tujuannya.
        </p>
        <p class="mt-2 text-sm text-base-content/70">
            Anda dapat menggunakan penyedia layanan SMTP gratis atau yang disediakan oleh institusi
            Anda.
        </p>

        <div class="mt-8 flex items-center gap-4">
            <x-ui::button class="btn-secondary btn-outline" label="Kembali" wire:click="backToPrev" />
            <x-ui::button
                class="btn-primary"
                label="Simpan & Lanjutkan"
                wire:click="save"
            />
        </div>
    </x-slot>

    <x-slot:content>
        <div class="space-y-4">
            <x-ui::input label="SMTP Host" wire:model="mail_host" placeholder="smtp.example.com" />
            <div class="grid grid-cols-2 gap-4">
                <x-ui::input label="SMTP Port" wire:model="mail_port" placeholder="587" />
                <x-ui::input label="Encryption" wire:model="mail_encryption" placeholder="tls" />
            </div>
            <x-ui::input label="Username" wire:model="mail_username" />
            <x-ui::input label="Password" wire:model="mail_password" type="password" />
            <hr class="my-4 border-base-200" />
            <x-ui::input label="Email Pengirim" wire:model="mail_from_address" placeholder="no-reply@school.id" />
            <x-ui::input label="Nama Pengirim" wire:model="mail_from_name" />
        </div>
    </x-slot>
</x-setup::layouts.setup-wizard>
