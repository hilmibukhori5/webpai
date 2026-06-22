<x-app-layout>
    <x-slot name="header">
        <h2 class="font-heading font-semibold text-xl text-slate-800 leading-tight">Upload Pengajuan Manual</h2>
    </x-slot>

    <div class="py-8 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        @if (session('status'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-5 py-3 text-sm">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl px-5 py-3 text-sm">
                {{ session('error') }}
            </div>
        @endif

        {{-- Current state --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-6">
            <h3 class="font-heading font-semibold text-slate-800 mb-4">Data Saat Ini</h3>

            @if ($totalEntries === 0)
                <p class="text-slate-400 text-sm">Belum ada data pengajuan manual yang diupload.</p>
            @else
                <p class="text-sm text-slate-600 mb-4">
                    Total <strong class="text-slate-900">{{ $totalEntries }}</strong> entri ter-upload.
                </p>
                <table class="text-sm w-full">
                    <thead>
                        <tr class="text-xs font-medium text-slate-500 uppercase tracking-wide border-b border-slate-100">
                            <th class="text-left pb-2">Modul</th>
                            <th class="text-right pb-2">Jumlah Mahasiswa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($summary as $item)
                            <tr class="border-b border-slate-50">
                                <td class="py-2 text-slate-700">
                                    <span class="font-mono text-xs text-slate-400">{{ $item->paiModule->code }}</span>
                                    {{ $item->paiModule->name }}
                                </td>
                                <td class="py-2 text-right font-semibold text-slate-900">{{ $item->total }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <form method="POST" action="{{ route('admin.manual-submissions.destroy') }}" class="mt-5"
                      onsubmit="return confirm('Hapus semua data pengajuan manual? Tindakan ini tidak dapat dibatalkan.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="text-xs text-red-600 hover:text-red-700 border border-red-200 hover:border-red-300 bg-red-50 hover:bg-red-100 rounded-lg px-3 py-1.5 transition-colors font-medium">
                        Hapus Semua Data Manual
                    </button>
                </form>
            @endif
        </div>

        {{-- Upload form --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-6">
            <h3 class="font-heading font-semibold text-slate-800 mb-1">Upload File Excel</h3>
            <p class="text-sm text-slate-500 mb-5">
                Upload menimpa entri yang sama (NIM + modul yang sama di-update, bukan duplikat).
                Data lama dari NIM/modul yang tidak ada di file baru tetap tersimpan.
            </p>

            <form method="POST" action="{{ route('admin.manual-submissions.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div>
                    <label class="text-sm font-medium text-slate-700 block mb-1.5">
                        File Excel <span class="text-red-500">*</span>
                    </label>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                        class="block w-full text-sm border border-slate-300 rounded-xl px-3 py-2 text-slate-600
                               file:mr-3 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-xs
                               file:font-medium file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                    @error('file')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-slate-700 block mb-1.5">
                        Catatan (opsional) — mis. "Batch 2024"
                    </label>
                    <input type="text" name="note" value="{{ old('note') }}" maxlength="100"
                        placeholder="Batch 2024"
                        class="block w-full text-sm border border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm px-3 py-2">
                </div>

                <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-5 py-2.5 text-sm font-medium transition-colors">
                    Import
                </button>
            </form>
        </div>

        {{-- Format guide --}}
        <div class="bg-indigo-50/60 border border-indigo-100 rounded-2xl p-5 text-sm text-slate-700">
            <p class="font-medium text-slate-900 mb-2">Format file yang didukung</p>
            <table class="text-xs w-full">
                <thead>
                    <tr class="text-slate-500 uppercase tracking-wide">
                        <th class="text-left pb-1">Kolom A</th>
                        <th class="text-left pb-1">Kolom B</th>
                        <th class="text-left pb-1">Kolom C</th>
                        <th class="text-left pb-1">Kolom D</th>
                    </tr>
                </thead>
                <tbody class="text-slate-600">
                    <tr class="font-medium"><td>NO</td><td>NIM</td><td>NAMA</td><td>MODUL PAI YANG DISETUJUI</td></tr>
                    <tr><td>1</td><td>225091000111008</td><td>Stella Paulina</td><td>A30 EKONOMI</td></tr>
                    <tr class="text-slate-400"><td></td><td></td><td></td><td>A40 AKUNTANSI</td></tr>
                    <tr><td>2</td><td>215091001111010</td><td>Vivi Anggraeny</td><td>A10 Matematika Keuangan</td></tr>
                    <tr class="text-slate-400"><td></td><td></td><td></td><td>A50 Metode Statistika</td></tr>
                </tbody>
            </table>
            <p class="mt-3 text-slate-500 text-xs">
                Baris dengan NIM kosong dianggap lanjutan dari mahasiswa di baris sebelumnya.
                Kolom A (NO) boleh kosong. Kode modul diambil dari awal kolom D (mis. "A30 EKONOMI" → modul A30).
            </p>
        </div>

        <a href="{{ route('admin.eligibility.index') }}"
           class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-indigo-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Cek Eligibility
        </a>

    </div>
</x-app-layout>
