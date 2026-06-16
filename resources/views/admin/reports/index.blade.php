<x-app-layout>
    <x-slot name="header">
        <h2 class="font-heading font-semibold text-xl text-slate-900 leading-tight">
            {{ __('Laporan Penyetaraan') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <p class="text-sm text-slate-500">
                Download laporan modul yang sudah <strong>disetujui</strong>, dipisah per skema —
                soalnya format nilainya beda: PKS Lama pakai nilai huruf (NH, mis. A, B+), PKS Baru
                pakai nilai angka (NA, mis. 80, 83.2). Tiap baris di laporan = 1 modul yang
                disetujui untuk 1 mahasiswa, lengkap dengan rincian matkul yang dipakai sebagai
                dasar penyetaraan.
            </p>

            <div class="grid sm:grid-cols-2 gap-4">
                <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-3">
                    <div>
                        <h3 class="font-heading font-semibold text-slate-900">Laporan PKS Lama</h3>
                        <p class="text-sm text-slate-500 mt-1">
                            {{ $approvedLamaCount }} modul disetujui dengan skema ini. Kolom nilai
                            berisi huruf (A, B+, B, dst).
                        </p>
                    </div>
                    @if ($approvedLamaCount > 0)
                        <a href="{{ route('admin.reports.export', 'lama') }}" class="inline-flex bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-4 py-2.5 text-sm font-medium">
                            Download (.xlsx)
                        </a>
                    @else
                        <span class="inline-flex bg-slate-100 text-slate-400 rounded-xl px-4 py-2.5 text-sm font-medium cursor-not-allowed">
                            Belum ada data
                        </span>
                    @endif
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-3">
                    <div>
                        <h3 class="font-heading font-semibold text-slate-900">Laporan PKS Baru</h3>
                        <p class="text-sm text-slate-500 mt-1">
                            {{ $approvedBaruCount }} modul disetujui dengan skema ini. Kolom nilai
                            berisi angka (NA, mis. 80, 83.2).
                        </p>
                    </div>
                    @if ($approvedBaruCount > 0)
                        <a href="{{ route('admin.reports.export', 'baru') }}" class="inline-flex bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-4 py-2.5 text-sm font-medium">
                            Download (.xlsx)
                        </a>
                    @else
                        <span class="inline-flex bg-slate-100 text-slate-400 rounded-xl px-4 py-2.5 text-sm font-medium cursor-not-allowed">
                            Belum ada data
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
