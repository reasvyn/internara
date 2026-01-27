<div class="flex size-full flex-1 flex-col items-center justify-center">
    <x-ui::card
        class="border-base-300 w-full max-w-lg rounded-xl border text-center shadow-lg"
        title="Daftar Akun Administrator"
        subtitle="Buat akun utama untuk mengelola seluruh data sistem."
    >
        <x-ui::form class="text-start" wire:submit="register">
            <x-ui::input
                wire:model="form.name"
                type="text"
                label="Nama Lengkap"
                placeholder="Masukkan Nama Lengkap Anda"
                required
                disabled
            />
            <x-ui::input
                wire:model="form.email"
                type="email"
                label="Alamat Email"
                placeholder="Masukkan Alamat Email Anda"
                required
                autofocus
            />
            <x-ui::input
                wire:model="form.password"
                type="password"
                label="Kata Sandi"
                placeholder="Buat Kata Sandi Anda"
                hint="Perhatian! Gunakan kombinasi kata sandi yang sulit ditebak."
                required
            />
            <x-ui::input
                wire:model="form.password_confirmation"
                type="password"
                label="Konfirmasi Kata Sandi"
                placeholder="Ulangi Kata Sandi Anda"
                required
            />

            <div class="mt-4 flex flex-col gap-8">
                <div class="w-full space-y-2">
                    <x-ui::button
                        class="btn-primary w-full"
                        label="Buat Akun"
                        type="submit"
                        spinner
                    />

                    <p class="text-center text-xs">
                        Hati-hati! Jangan berikan informasi akun kepada siapapun.
                    </p>
                </div>
            </div>
        </x-ui::form>
    </x-ui::card>
</div>
