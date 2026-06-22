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
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-slate-50">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white border-b border-slate-200">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>

            <!-- Footer -->
            <footer class="border-t border-slate-200 bg-white mt-8">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <img src="https://stem.ub.ac.id/wp-content/uploads/2026/05/Logo-Website-sementara-1-1.png"
                             alt="FSTeM UB" class="h-7 w-auto opacity-60">
                        <div class="text-xs text-slate-500 leading-tight">
                            <div class="font-medium text-slate-600">Departemen Matematika — FSTeM UB</div>
                            <div>Sistem Penyetaraan Modul PAI</div>
                        </div>
                    </div>
                    <p class="text-xs text-slate-400">&copy; {{ date('Y') }} Universitas Brawijaya</p>
                </div>
            </footer>
        </div>
    </body>
</html>
