<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600|plus-jakarta-sans:500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased bg-slate-50">

        <!-- Top nav -->
        <header class="border-b border-slate-200 bg-white">
            <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
                <a href="/" class="flex items-center gap-3">
                    <img src="https://stem.ub.ac.id/wp-content/uploads/2026/05/Logo-Website-sementara-1-1.png"
                         alt="FSTeM UB" class="h-10 w-auto">
                    <div class="flex flex-col leading-tight">
                        <span class="font-heading font-semibold text-slate-900 leading-none">Penyetaraan Modul PAI</span>
                        <span class="text-xs text-slate-500 leading-none mt-0.5">Departemen Matematika FSTeM Universitas Brawijaya</span>
                    </div>
                </a>

                <nav class="flex items-center gap-3">
                    <a href="{{ route('manual') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 px-3 py-2">
                        Panduan
                    </a>
                    @auth
                        <a href="{{ Auth::user()->isAdmin() ? route('admin.students.index') : route('dashboard') }}"
                           class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-4 py-2 text-sm font-medium">
                            Ke dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 px-3 py-2">
                            Masuk
                        </a>
                        <a href="{{ route('register') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-4 py-2 text-sm font-medium">
                            Daftar
                        </a>
                    @endauth
                </nav>
            </div>
        </header>

        <!-- Hero -->
        <section class="bg-gradient-to-br from-indigo-600 to-violet-600 text-white">
            <div class="max-w-6xl mx-auto px-6 py-20 text-center space-y-6">
                <div class="flex items-center justify-center gap-3 flex-wrap">
                    <img src="https://stem.ub.ac.id/wp-content/uploads/2026/05/Logo-Website-sementara-1-1.png"
                         alt="FSTeM UB" class="h-10 w-auto brightness-0 invert opacity-90">
                    <div class="text-left">
                        <div class="text-indigo-100 text-xs font-medium">Departemen Matematika</div>
                        <div class="text-white font-heading font-semibold text-sm leading-tight">FSTeM Universitas Brawijaya</div>
                    </div>
                </div>
                <span class="inline-block bg-white/10 text-indigo-50 text-xs font-medium px-3 py-1 rounded-full">
                    Khusus mahasiswa S1 Ilmu Aktuaria &amp; S1 Matematika
                </span>
                <h1 class="font-heading text-3xl sm:text-4xl font-semibold leading-tight max-w-2xl mx-auto">
                    Setarakan matkul yang sudah kamu lulus ke Modul PAI ASAI
                </h1>
                <p class="text-indigo-100 max-w-xl mx-auto leading-relaxed">
                    Nggak perlu hitung manual atau nebak-nebak skema. Sistem ini otomatis mengecek
                    eligibility kamu per modul berdasarkan nilai yang sudah diinput admin — kamu
                    cuma perlu daftar, cek status, lalu ajukan.
                </p>
                <div class="flex items-center justify-center gap-3">
                    @guest
                        <a href="{{ route('register') }}" class="bg-white text-indigo-700 hover:bg-indigo-50 rounded-xl px-5 py-2.5 text-sm font-medium">
                            Daftar sekarang
                        </a>
                        <a href="{{ route('login') }}" class="border border-white/40 hover:bg-white/10 rounded-xl px-5 py-2.5 text-sm font-medium">
                            Sudah punya akun? Masuk
                        </a>
                    @else
                        <a href="{{ Auth::user()->isAdmin() ? route('admin.students.index') : route('dashboard') }}" class="bg-white text-indigo-700 hover:bg-indigo-50 rounded-xl px-5 py-2.5 text-sm font-medium">
                            Ke dashboard
                        </a>
                    @endguest
                </div>
            </div>
        </section>

        <!-- Cara kerja -->
        <section class="max-w-6xl mx-auto px-6 py-16">
            <h2 class="font-heading text-2xl font-semibold text-slate-900 text-center">Cara kerja</h2>
            <p class="text-slate-500 text-center mt-2 max-w-lg mx-auto">
                Tiga langkah sederhana dari daftar sampai modul kamu disetujui.
            </p>

            <div class="grid sm:grid-cols-3 gap-6 mt-10">
                <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-2">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 font-heading font-semibold">1</span>
                    <h3 class="font-heading font-semibold text-slate-900">Daftar &amp; lengkapi profil</h3>
                    <p class="text-sm text-slate-500">
                        Isi No Induk (NIM) &amp; prodi kamu. No Induk inilah yang dipakai sistem buat
                        mencocokkan nilai matkul yang sudah diinput admin.
                    </p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-2">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 font-heading font-semibold">2</span>
                    <h3 class="font-heading font-semibold text-slate-900">Cek eligibility otomatis</h3>
                    <p class="text-sm text-slate-500">
                        Tiap modul A10&ndash;A70 dicek otomatis: lolos PKS Baru (percentile),
                        Adendum PKS Lama (rata-rata nilai), atau belum eligible — lengkap dengan alasannya.
                    </p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-2">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 font-heading font-semibold">3</span>
                    <h3 class="font-heading font-semibold text-slate-900">Ajukan &amp; tunggu approval</h3>
                    <p class="text-sm text-slate-500">
                        Setuju skema &amp; biaya, kirim pengajuan. Admin review, dan kamu dapat
                        notifikasi email begitu disetujui atau ditolak.
                    </p>
                </div>
            </div>
        </section>

        <!-- Modul yang didukung -->
        <section class="bg-white border-y border-slate-200">
            <div class="max-w-6xl mx-auto px-6 py-16">
                <h2 class="font-heading text-2xl font-semibold text-slate-900 text-center">Modul yang didukung</h2>
                <p class="text-slate-500 text-center mt-2 max-w-lg mx-auto">
                    7 modul level ASAI, masing-masing punya skema &amp; matkul komponen sendiri.
                </p>

                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-10">
                    @foreach ($modules as $module)
                        <div class="flex items-center gap-3 rounded-2xl border border-slate-200 p-4">
                            <span class="inline-block bg-module-{{ strtolower($module->code) }} text-white text-xs font-semibold px-2 py-1 rounded-lg">
                                {{ $module->code }}
                            </span>
                            <span class="text-sm font-medium text-slate-700">{{ $module->name }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <footer class="border-t border-slate-200 bg-white mt-4">
            <div class="max-w-6xl mx-auto px-6 py-6 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <img src="https://stem.ub.ac.id/wp-content/uploads/2026/05/Logo-Website-sementara-1-1.png"
                         alt="FSTeM UB" class="h-8 w-auto opacity-70">
                    <div class="text-xs text-slate-500 leading-tight">
                        <div class="font-medium text-slate-700">Departemen Matematika</div>
                        <div>Fakultas Sains dan Teknologi — FSTeM, Universitas Brawijaya</div>
                    </div>
                </div>
                <p class="text-xs text-slate-400">&copy; {{ date('Y') }} Sistem Penyetaraan Modul PAI</p>
            </div>
        </footer>
    </body>
</html>
