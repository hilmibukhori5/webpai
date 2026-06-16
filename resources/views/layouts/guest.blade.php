<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600|plus-jakarta-sans:500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div class="min-h-screen flex bg-slate-50">

            <!-- Panel kiri: brand + penjelasan (disembunyikan di mobile) -->
            <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-indigo-600 to-violet-600 text-white flex-col justify-between p-12">
                <a href="/" class="font-heading text-lg font-semibold">
                    Penyetaraan Modul PAI
                </a>

                <div class="space-y-6 max-w-md">
                    <h1 class="font-heading text-3xl font-semibold leading-tight">
                        Setarakan matkul yang sudah kamu lulus ke Modul PAI ASAI
                    </h1>
                    <p class="text-indigo-100 text-sm leading-relaxed">
                        Khusus mahasiswa S1 Ilmu Aktuaria &amp; S1 Matematika, Departemen Matematika UB. Sistem ini
                        otomatis mengecek eligibility kamu berdasarkan nilai yang sudah diinput
                        admin — kamu cuma perlu ajukan, lalu tunggu approval.
                    </p>
                    <div class="flex flex-wrap gap-2" aria-hidden="true">
                        <span class="bg-module-a10 text-white text-xs font-semibold px-2 py-1 rounded-lg">A10</span>
                        <span class="bg-module-a20 text-white text-xs font-semibold px-2 py-1 rounded-lg">A20</span>
                        <span class="bg-module-a30 text-white text-xs font-semibold px-2 py-1 rounded-lg">A30</span>
                        <span class="bg-module-a40 text-white text-xs font-semibold px-2 py-1 rounded-lg">A40</span>
                        <span class="bg-module-a50 text-white text-xs font-semibold px-2 py-1 rounded-lg">A50</span>
                        <span class="bg-module-a60 text-white text-xs font-semibold px-2 py-1 rounded-lg">A60</span>
                        <span class="bg-module-a70 text-white text-xs font-semibold px-2 py-1 rounded-lg">A70</span>
                    </div>
                </div>

                <p class="text-indigo-200 text-xs">
                    &copy; {{ date('Y') }} Penyetaraan Modul PAI &mdash; UB
                </p>
            </div>

            <!-- Panel kanan: form -->
            <div class="flex-1 flex flex-col items-center justify-center px-6 py-12">
                <div class="w-full sm:max-w-md">
                    <div class="lg:hidden text-center mb-6">
                        <a href="/" class="font-heading text-lg font-semibold text-slate-900">
                            Penyetaraan Modul PAI
                        </a>
                    </div>

                    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 sm:p-8">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
