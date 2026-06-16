<x-app-layout>
    <x-slot name="header">
        <h2 class="font-heading font-semibold text-xl text-slate-900 leading-tight">
            {{ __('Import Nilai') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg p-4 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-rose-50 border border-rose-200 text-rose-700 rounded-lg p-4 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            @if (session('importErrors'))
                <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-lg p-4 text-sm space-y-1">
                    <p class="font-medium">Sebagian baris dilewati karena tidak valid:</p>
                    <ul class="list-disc list-inside">
                        @foreach (session('importErrors') as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <p class="text-sm text-slate-500">
                    Upload nilai mahasiswa per matkul per semester. Tiap baris yang valid masuk
                    ke <code class="text-xs bg-slate-100 px-1 py-0.5 rounded">course_grades</code>,
                    lalu batas bawah percentile (<code class="text-xs bg-slate-100 px-1 py-0.5 rounded">course_thresholds</code>)
                    untuk matkul itu otomatis dihitung ulang dari semua nilai yang ter-pool
                    (semua semester, bukan cuma yang baru diupload).
                </p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-6">
                <div>
                    <p class="text-sm text-slate-600">
                        Format file: kolom <strong>No Induk, Nama, NA, NH</strong> (urutan bebas, nama
                        kolom harus persis). NA harus angka 0&ndash;100, NH harus salah satu dari
                        A/B+/B/C+/C/D+/D/E.
                    </p>
                    <a href="{{ asset('samples/course_grades_sample.csv') }}" class="text-indigo-600 hover:text-indigo-800 text-sm underline" download>
                        Download contoh file (CSV)
                    </a>
                </div>

                <form method="POST" action="{{ route('admin.grades.import.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="course_id" value="Matkul" />
                        <select id="course_id" name="course_id" class="mt-1 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm" required>
                            <option value="">-- pilih matkul --</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->id }}" @selected(old('course_id') == $course->id)>
                                    {{ $course->code }} - {{ $course->name }} ({{ $course->sks }} sks)
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('course_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="semester" value="Label semester" />
                        <x-text-input id="semester" name="semester" type="text" class="mt-1 block w-full" placeholder='mis. "Genap 2223"' :value="old('semester')" required />
                        <p class="text-xs text-slate-400 mt-1">Bebas formatnya, asal konsisten — dipakai cuma buat catatan/audit.</p>
                        <x-input-error :messages="$errors->get('semester')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="file" value="File (xlsx/xls/csv)" />
                        <input id="file" name="file" type="file" accept=".xlsx,.xls,.csv" class="mt-1 block w-full text-sm" required>
                        <p class="text-xs text-slate-400 mt-1">Maksimal 5MB.</p>
                        <x-input-error :messages="$errors->get('file')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end">
                        <x-primary-button>Import</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-indigo-50/60 border border-indigo-100 rounded-2xl p-5 text-sm text-slate-700">
                <p class="font-medium text-slate-900 mb-1">Kenapa threshold di-recompute otomatis?</p>
                <p>
                    Batas bawah PKS Baru itu percentile dari NA semua mahasiswa yang pernah ambil
                    matkul itu (bukan angka fix) — jadi tiap kali ada data baru masuk, batasnya bisa
                    sedikit berubah. Kalau perlu recompute manual tanpa import baru, jalankan
                    <code class="text-xs bg-white px-1 py-0.5 rounded border border-indigo-100">php artisan thresholds:recompute</code>.
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
