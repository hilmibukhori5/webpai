<x-app-layout>
    <x-slot name="header">
        <h2 class="font-heading font-semibold text-xl text-slate-900 leading-tight">
            Import Nilai
        </h2>
    </x-slot>

    <div class="py-12" x-data="gradeMatrix()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

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
                        @foreach (session('importErrors') as $msg)
                            <li>{{ $msg }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Legend + filter --}}
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">

                {{-- Legend --}}
                <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-slate-500">
                    <span class="flex items-center gap-2">
                        <span class="w-5 h-5 rounded-lg bg-emerald-100 border border-emerald-300 flex items-center justify-center text-emerald-700 font-bold">✓</span>
                        Sudah diupload
                    </span>
                    <span class="flex items-center gap-2">
                        <span class="w-5 h-5 rounded-lg bg-white border border-slate-300 flex items-center justify-center text-slate-400">+</span>
                        Belum diupload
                    </span>
                    <span class="flex items-center gap-2">
                        <span class="w-5 h-5 rounded-lg bg-amber-100 border border-amber-300 flex items-center justify-center text-amber-600">—</span>
                        Dilewati
                    </span>
                </div>

                {{-- Filters --}}
                <div class="flex gap-2 sm:ml-auto">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input
                            type="text"
                            x-model="search"
                            placeholder="Cari matkul..."
                            class="pl-8 pr-3 py-1.5 text-sm border border-slate-300 rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500 w-48"
                        >
                    </div>
                    <select
                        x-model="semType"
                        class="text-sm border border-slate-300 rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-1.5 pl-3 pr-8"
                    >
                        <option value="">Semua semester</option>
                        <option value="Genap">Genap</option>
                        <option value="Ganjil">Ganjil</option>
                    </select>
                </div>
            </div>

            {{-- Matrix --}}
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="sticky left-0 z-20 bg-slate-50 text-left px-5 py-3.5 font-medium text-slate-500 text-xs tracking-wide uppercase min-w-[260px] shadow-[2px_0_4px_-2px_rgba(0,0,0,0.08)]">
                                    Matkul
                                </th>
                                <th class="text-center px-4 py-3.5 font-medium text-slate-500 text-xs tracking-wide uppercase w-28">Semester</th>
                                @foreach ($years as $year)
                                    <th class="text-center px-3 py-3.5 font-medium text-slate-500 text-xs tracking-wide uppercase w-28">
                                        {{ substr($year, 0, 2) }}/{{ substr($year, 2, 2) }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($matrix as $group)
                                {{-- Module header --}}
                                <tr>
                                    <td colspan="{{ 2 + count($years) }}"
                                        class="sticky left-0 px-5 py-2 text-xs font-semibold tracking-widest text-indigo-200 bg-slate-700 uppercase">
                                        {{ $group['module']->code }} &mdash; {{ $group['module']->name }}
                                    </td>
                                </tr>

                                @php $prevProdi = null; @endphp
                                @foreach ($group['rows'] as $row)
                                    {{-- Prodi sub-header untuk modul yang punya kursus dari 2 prodi (mis. A20) --}}
                                    @if ($group['hasMultipleProdi'] && $row['prodi'] !== $prevProdi)
                                        @php $prevProdi = $row['prodi']; @endphp
                                        <tr>
                                            <td colspan="{{ 2 + count($years) }}"
                                                class="px-5 py-1.5 text-xs font-medium text-slate-400 bg-slate-50/80 border-y border-slate-100 tracking-wide">
                                                {{ $row['prodi'] }}
                                            </td>
                                        </tr>
                                    @endif
                                    <tr
                                        class="border-b border-slate-100 transition-colors"
                                        x-show="matchesFilter(@js($row['course']->name), @js($row['course']->code), @js($row['course']->semester_type))"
                                    >
                                        {{-- Course name (sticky) --}}
                                        <td class="sticky left-0 z-10 bg-white px-5 py-3.5 shadow-[2px_0_4px_-2px_rgba(0,0,0,0.06)]">
                                            <span class="font-mono text-xs text-slate-400">{{ $row['course']->code }}</span>
                                            <div class="font-medium text-slate-800 mt-0.5">{{ $row['course']->name }}</div>
                                        </td>

                                        {{-- Semester type --}}
                                        <td class="px-4 py-3.5 text-center">
                                            <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium
                                                {{ $row['course']->semester_type === 'Genap' ? 'bg-blue-50 text-blue-700' : 'bg-orange-50 text-orange-700' }}">
                                                {{ $row['course']->semester_type }}
                                            </span>
                                        </td>

                                        {{-- Year cells --}}
                                        @foreach ($years as $year)
                                            @php
                                                $cell = $row['cells'][$year];
                                                $period = "{$row['course']->semester_type} {$year}";
                                            @endphp
                                            <td class="px-3 py-3.5 text-center">
                                                <button
                                                    type="button"
                                                    @click="openModal(
                                                        {{ $row['course']->id }},
                                                        @js($row['course']->name),
                                                        '{{ $year }}',
                                                        @js($period),
                                                        '{{ $cell['status'] }}',
                                                        {{ $cell['count'] }},
                                                        @js($cell['skip']?->note ?? ''),
                                                        {{ $cell['skip']?->id ?? 'null' }}
                                                    )"
                                                    title="{{ $period }}"
                                                    class="inline-flex items-center justify-center min-w-[5rem] h-9 px-3 rounded-xl border text-xs font-semibold transition-colors cursor-pointer
                                                        @if ($cell['status'] === 'uploaded')
                                                            bg-emerald-50 border-emerald-200 text-emerald-700 hover:bg-emerald-100
                                                        @elseif ($cell['status'] === 'skipped')
                                                            bg-amber-50 border-amber-200 text-amber-700 hover:bg-amber-100
                                                        @else
                                                            bg-white border-slate-200 text-slate-400 hover:border-indigo-300 hover:text-indigo-500 hover:bg-indigo-50/50
                                                        @endif"
                                                >
                                                    @if ($cell['status'] === 'uploaded')
                                                        <svg class="w-3 h-3 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                        </svg>
                                                        {{ $cell['count'] }}
                                                    @elseif ($cell['status'] === 'skipped')
                                                        <svg class="w-3 h-3 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                        </svg>
                                                        Lewati
                                                    @else
                                                        <svg class="w-3 h-3 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                                        </svg>
                                                        Upload
                                                    @endif
                                                </button>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>

            <div class="bg-indigo-50/60 border border-indigo-100 rounded-2xl p-5 text-sm text-slate-700">
                <p class="font-medium text-slate-900 mb-1">Format file & cara kerja threshold</p>
                <p>
                    Kolom wajib: <strong>No Induk, Nama, NA, NH</strong> (nama kolom harus persis, urutan bebas).
                    NA harus angka 0&ndash;100, NH harus A/B+/B/C+/C/D+/D/E. Upload ulang ke cell yang sama
                    akan <strong>mengganti semua data lama</strong> untuk matkul &amp; tahun itu.
                    Threshold PKS Baru otomatis dihitung ulang setiap ada import baru.
                </p>
                <a href="{{ asset('samples/course_grades_sample.csv') }}" class="text-indigo-600 hover:text-indigo-800 underline mt-1 inline-block" download>
                    Download contoh file (CSV)
                </a>
            </div>
        </div>

        {{-- Modal --}}
        <div
            x-show="isOpen"
            x-cloak
            class="fixed inset-0 z-50 bg-slate-900/40 flex items-center justify-center p-4"
            @click.self="isOpen = false"
            @keydown.escape.window="isOpen = false"
        >
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md" @click.stop>
                <div class="p-6 space-y-5">

                    {{-- Header --}}
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-medium text-slate-500" x-text="period"></p>
                            <h4 class="font-heading font-semibold text-slate-900 mt-0.5 text-base leading-snug" x-text="courseName"></h4>
                        </div>
                        <button @click="isOpen = false"
                            class="text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg w-8 h-8 flex items-center justify-center transition-colors flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Uploaded --}}
                    <div x-show="status === 'uploaded'" class="space-y-4">
                        <div class="flex items-center gap-3 bg-emerald-50 border border-emerald-200 rounded-xl p-4">
                            <div class="w-9 h-9 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-emerald-800" x-text="count + ' mahasiswa ter-import'"></p>
                                <p class="text-xs text-emerald-600 mt-0.5">Upload ulang untuk mengganti semua data yang ada.</p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('admin.grades.import.store') }}" enctype="multipart/form-data" class="space-y-3">
                            @csrf
                            <input type="hidden" name="course_id" :value="courseId">
                            <input type="hidden" name="year" :value="year">
                            <div>
                                <label class="text-xs font-medium text-slate-700 block mb-1.5">File baru (xlsx / xls / csv, maks 5MB)</label>
                                <input type="file" name="file" accept=".xlsx,.xls,.csv"
                                    class="block w-full text-sm border border-slate-300 rounded-xl px-3 py-2 text-slate-600 file:mr-3 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200"
                                    required>
                            </div>
                            <button type="submit"
                                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-4 py-2.5 text-sm font-medium transition-colors">
                                Ganti Data (data lama dihapus)
                            </button>
                        </form>
                    </div>

                    {{-- Empty --}}
                    <div x-show="status === 'empty'" class="space-y-4">
                        <form method="POST" action="{{ route('admin.grades.import.store') }}" enctype="multipart/form-data" class="space-y-3">
                            @csrf
                            <input type="hidden" name="course_id" :value="courseId">
                            <input type="hidden" name="year" :value="year">
                            <div>
                                <label class="text-xs font-medium text-slate-700 block mb-1.5">File nilai (xlsx / xls / csv, maks 5MB)</label>
                                <input type="file" name="file" accept=".xlsx,.xls,.csv"
                                    class="block w-full text-sm border border-slate-300 rounded-xl px-3 py-2 text-slate-600 file:mr-3 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200"
                                    required>
                            </div>
                            <button type="submit"
                                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-4 py-2.5 text-sm font-medium transition-colors">
                                Import
                            </button>
                        </form>
                        <div class="border-t border-slate-100 pt-4">
                            <p class="text-xs text-slate-500 mb-2">Atau tandai sebagai tidak perlu diupload:</p>
                            <form method="POST" action="{{ route('admin.grades.import.skip') }}" class="flex gap-2">
                                @csrf
                                <input type="hidden" name="course_id" :value="courseId">
                                <input type="hidden" name="period" :value="period">
                                <input type="text" name="note" placeholder="Alasan (opsional)..."
                                    class="flex-1 text-sm border border-slate-300 rounded-xl px-3 py-1.5 focus:border-indigo-500 focus:ring-indigo-500">
                                <button type="submit"
                                    class="bg-amber-100 hover:bg-amber-200 text-amber-800 rounded-xl px-3 py-1.5 text-sm font-medium transition-colors whitespace-nowrap">
                                    Lewati
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Skipped --}}
                    <div x-show="status === 'skipped'" class="space-y-4">
                        <div class="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-xl p-4">
                            <div class="w-9 h-9 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-amber-800">Ditandai dilewati</p>
                                <p class="text-xs text-amber-700 mt-0.5" x-show="skipNote" x-text="skipNote"></p>
                                <p class="text-xs text-amber-400 mt-0.5 italic" x-show="!skipNote">(tanpa catatan)</p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('admin.grades.import.store') }}" enctype="multipart/form-data" class="space-y-3">
                            @csrf
                            <input type="hidden" name="course_id" :value="courseId">
                            <input type="hidden" name="year" :value="year">
                            <p class="text-xs text-slate-500">Upload sekarang? Status "dilewati" otomatis dihapus.</p>
                            <input type="file" name="file" accept=".xlsx,.xls,.csv"
                                class="block w-full text-sm border border-slate-300 rounded-xl px-3 py-2 text-slate-600 file:mr-3 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200"
                                required>
                            <button type="submit"
                                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-4 py-2.5 text-sm font-medium transition-colors">
                                Import
                            </button>
                        </form>
                        <form :action="skipDeleteUrl" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="flex items-center gap-1.5 text-sm text-slate-400 hover:text-slate-600 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Hapus status dilewati
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
    function gradeMatrix() {
        return {
            // Modal
            isOpen: false,
            courseId: null,
            courseName: '',
            year: '',
            period: '',
            status: '',
            count: 0,
            skipNote: '',
            skipDeleteUrl: '',

            // Filters
            search: '',
            semType: '',

            openModal(courseId, courseName, year, period, status, count, skipNote, skipId) {
                this.courseId = courseId;
                this.courseName = courseName;
                this.year = year;
                this.period = period;
                this.status = status;
                this.count = count;
                this.skipNote = skipNote || '';
                this.skipDeleteUrl = '{{ route('admin.grades.import.unskip', '__ID__') }}'.replace('__ID__', skipId);
                this.isOpen = true;
            },

            matchesFilter(name, code, semesterType) {
                const q = this.search.trim().toLowerCase();
                const matchesSearch = !q || name.toLowerCase().includes(q) || code.toLowerCase().includes(q);
                const matchesSem = !this.semType || this.semType === semesterType;
                return matchesSearch && matchesSem;
            },
        }
    }
    </script>
</x-app-layout>
