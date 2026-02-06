<x-ui::card
    class="w-full max-w-lg text-center"
    title="Masuk ke Akun Anda"
    subtitle="Gunakan email atau nama pengguna Anda untuk melanjutkan."
>
    <div class="flex flex-col gap-8">
        <x-ui::form class="text-start" wire:submit="login">
            <x-ui::input
                type="text"
                label="Email atau Username"
                placeholder="Masukkan Email atau Username Anda"
                wire:model="identifier"
                required
            />

            <div class="relative w-full">
                <x-ui::input
                    type="password"
                    label="Kata Sandi"
                    placeholder="Masukkan Kata Sandi Anda"
                    wire:model="password"
                    required
                />

                @if (\Illuminate\Support\Facades\Route::has('forgot-password'))
                    <a
                        class="absolute right-0 top-2 text-xs font-medium underline"
                        href="{{ route('forgot-password') }}"
                    >
                        Lupa Kata Sandi Anda?
                    </a>
                @endif
            </div>

            <x-ui::checkbox label="Ingat saya" wire:model="remember" />

            <x-ui::turnstile field-name="captcha_token" />

            <div class="mt-4 flex w-full flex-col gap-8">
                <x-ui::button
                    priority="primary"
                    class="w-full"
                    label="Masuk Sekarang"
                    type="submit"
                    spinner
                />

                @if (\Illuminate\Support\Facades\Route::has('register'))
                    <p class="text-center text-sm">
                        Belum punya akun?
                        <a
                            class="font-medium underline"
                            href="{{ route('register') }}"
                            wire:navigate
                        >
                            Daftar Sekarang
                        </a>
                    </p>
                @endif
            </div>
        </x-ui::form>
    </div>
</x-ui::card>
