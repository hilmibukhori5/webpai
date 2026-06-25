<x-app-layout>
    <x-slot name="header">
        <h2 class="font-heading font-semibold text-xl text-slate-900 leading-tight">
            {{ __('Laporan Penyetaraan') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <p class="text-sm text-slate-500">
                Download laporan semua modul yang sudah <strong>disetujui</strong> dalam satu file. Kolom
                <strong>Klausul PKS</strong> di bagian akhir menunjukkan skema tiap baris: "PKS Lama" (nilai huruf NH)
                atau "PKS Baru" (nilai angka NA). Tiap baris = 1 modul yang disetujui untuk 1 mahasiswa.
            </p>

            <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-3">
                <div>
                    <h3 class="font-heading font-semibold text-slate-900">Laporan Penyetaraan (Gabungan)</h3>
                    <p class="text-sm text-slate-500 mt-1">
                        {{ $approvedCount }} modul disetujui. Kolom Klausul PKS membedakan skema per baris.
                    </p>
                </div>
                @if ($approvedCount > 0)
                    <a href="{{ route('admin.reports.export') }}" class="inline-flex bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-4 py-2.5 text-sm font-medium">
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
</x-app-layout>
