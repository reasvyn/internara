<x-ui::card
    class="w-full max-w-lg text-center"
    title="Daftar Akun Siswa Internara"
    subtitle="Isi data diri Anda untuk memulai perjalanan magang."
>
    <x-ui::form class="text-start" wire:submit="register">
        <x-ui::input
            type="text"
            label="Nama Lengkap"
            placeholder="Masukkan Nama Lengkap Anda"
            wire:model="name"
            required
        />
        <x-ui::input
            type="email"
            label="Alamat Email"
            placeholder="Masukkan Alamat Email Anda"
            wire:model="email"
            required
        />
        <x-ui::input
            type="password"
            label="Kata Sandi"
            placeholder="Buat Kata Sandi Anda"
            wire:model="password"
            required
        />
        <x-ui::input
            type="password"
            label="Konfirmasi Kata Sandi"
            placeholder="Ulangi Kata Sandi Anda"
            wire:model="password_confirmation"
            required
        />
        @php
            $policyRoute = \Illuminate\Support\Facades\Route::has('privacy-policy') ? route('privacy-policy') : '#';
        @endphp

        <div class="mt-4 flex flex-col gap-8">
            <div class="w-full space-y-2">
                <x-ui::button
                    class="btn-primary w-full"
                    label="Daftar Sekarang"
                    type="submit"
                    spinner
                />

                <p class="text-center text-xs">
                    Dengan menekan tombol
                    <b>Daftar Sekarang</b>
                    , Anda otomatis menyetujui
                    <a class="underline" href="{{ $policyRoute }}">Kebijakan Privasi kami.</a>
                </p>
            </div>

            @if (\Illuminate\Support\Facades\Route::has('login'))
                <p class="text-center text-sm">
                    Sudah memiliki akun?
                    <a class="font-medium underline" href="{{ route('login') }}" wire:navigate>
                        Masuk
                    </a>
                </p>
            @endif
        </div>
    </x-ui::form>
</x-ui::card>
