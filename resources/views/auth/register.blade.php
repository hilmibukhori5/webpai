<x-guest-layout>
    <div class="mb-6">
        <h2 class="font-heading text-xl font-semibold text-slate-900">Daftar mahasiswa</h2>
        <p class="text-sm text-slate-500 mt-1">
            Daftarkan dirimu sebagai mahasiswa S1 Ilmu Aktuaria atau S1 Matematika untuk mulai
            cek eligibility penyetaraan matkul kamu ke Modul PAI A10&ndash;A70.
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="nim@student.ub.ac.id" />
            <p class="text-xs text-slate-500 mt-1">Harus menggunakan email UB (@student.ub.ac.id).</p>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- No Induk (NIM) -->
        <div class="mt-4">
            <x-input-label for="no_induk" value="No Induk (NIM)" />
            <x-text-input id="no_induk" class="block mt-1 w-full" type="text" name="no_induk" :value="old('no_induk')" required autocomplete="off" />
            <p class="text-xs text-slate-500 mt-1">
                Dipakai buat mencocokkan nilai matkul kamu yang sudah diinput admin — pastikan
                NIM-nya benar &amp; cuma angka.
            </p>
            <x-input-error :messages="$errors->get('no_induk')" class="mt-2" />
        </div>

        <!-- Prodi -->
        <div class="mt-4">
            <x-input-label for="prodi" value="Program Studi" />
            <select id="prodi" name="prodi" class="mt-1 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm" required>
                <option value="">-- pilih prodi --</option>
                @foreach ($prodiOptions as $option)
                    <option value="{{ $option }}" @selected(old('prodi') === $option)>{{ $option }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('prodi')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-6">
            <a class="underline text-sm text-slate-600 hover:text-slate-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
