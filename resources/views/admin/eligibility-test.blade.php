<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Uji Coba Eligibility
        </h2>
    </x-slot>

    {{-- Data PHP → JS: harus di script tag, BUKAN di atribut HTML (JSON mengandung " yang memutus atribut) --}}
    <script>
        window._elig = {
            moduleCourseMap: @json($moduleCourseMap),
            allCourses:      @json($allCourses),
            initModule:      @json(old('module_code', $input['module_code'] ?? '')),
            initProdi:       @json(old('prodi', $input['prodi'] ?? 'S1 Ilmu Aktuaria')),
            initGrades:      @json($input['grades'] ?? []),
        };
    </script>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6" x-data="eligibilityTest()">

            {{-- Info banner --}}
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-800">
                <strong>Alat debug:</strong> Data yang dimasukkan di sini <strong>tidak disimpan</strong> ke database —
                dijalankan dalam transaksi yang langsung di-rollback. Threshold percentile diambil dari data nilai
                riil yang sudah diimport.
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                {{-- ===== FORM ===== --}}
                <div class="bg-white rounded-lg shadow-sm border border-slate-200">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Input Skenario</h3>
                    </div>

                    <form method="POST" action="{{ route('admin.eligibility-test.run') }}" class="p-6 space-y-5">
                        @csrf

                        {{-- Modul --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1">
                                Modul PAI
                            </label>
                            <select name="module_code" x-model="moduleCode"
                                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">-- Pilih Modul --</option>
                                @foreach ($modules as $m)
                                    <option value="{{ $m->code }}">{{ $m->code }} — {{ $m->name }}</option>
                                @endforeach
                            </select>
                            @error('module_code')
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Prodi --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1">
                                Program Studi
                            </label>
                            <select name="prodi" x-model="prodi"
                                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="S1 Ilmu Aktuaria">S1 Ilmu Aktuaria</option>
                                <option value="S1 Matematika">S1 Matematika</option>
                            </select>
                        </div>

                        {{-- Referensi matkul komponen --}}
                        <div x-show="moduleCode && Object.keys(currentModuleCourses).length > 0"
                             class="rounded-lg border border-slate-200 bg-slate-50 p-3 space-y-2 text-xs">
                            <p class="font-semibold text-slate-500 uppercase tracking-wide text-[10px]">Matkul komponen — klik untuk tambah baris</p>
                            <template x-for="[curriculum, courses] in Object.entries(currentModuleCourses)">
                                <div class="space-y-1">
                                    <p class="font-medium text-slate-500" x-text="curriculum === 'baru' ? '📗 Set Baru' : '📙 Set Lama'"></p>
                                    <div class="flex flex-wrap gap-1.5">
                                        <template x-for="course in courses">
                                            <button type="button"
                                                    @click="addCourse(course.code)"
                                                    class="inline-flex items-center gap-1 px-2 py-1 rounded-md border border-slate-300 bg-white text-slate-700 hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-colors text-xs">
                                                <span x-text="course.code"></span>
                                                <span class="text-slate-400" x-text="'(' + course.sks + ' sks)'"></span>
                                                <span class="text-slate-400">+</span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Baris nilai --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">
                                Daftar Nilai Mahasiswa
                            </label>

                            {{-- Header kolom --}}
                            <div class="grid grid-cols-12 gap-2 text-[10px] font-semibold text-slate-400 uppercase tracking-wide mb-1 px-0.5">
                                <div class="col-span-3">Kode MK</div>
                                <div class="col-span-2">Semester</div>
                                <div class="col-span-2">NA</div>
                                <div class="col-span-2">NH</div>
                                <div class="col-span-2">Threshold <span class="text-slate-300 font-normal normal-case">(opsional)</span></div>
                                <div class="col-span-1"></div>
                            </div>

                            <div class="space-y-2">
                                <template x-for="(row, index) in rows" :key="index">
                                    <div class="grid grid-cols-12 gap-2 items-start">
                                        {{-- Kode MK --}}
                                        <div class="col-span-3">
                                            <select :name="'grades[' + index + '][course_code]'"
                                                    x-model="row.course_code"
                                                    class="w-full rounded-md border-slate-300 text-xs shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-1.5">
                                                <option value="">-- MK --</option>
                                                <template x-for="c in allCourses">
                                                    <option :value="c.code" :selected="c.code === row.course_code"
                                                            x-text="c.code"></option>
                                                </template>
                                            </select>
                                        </div>
                                        {{-- Semester --}}
                                        <div class="col-span-2">
                                            <select :name="'grades[' + index + '][semester]'"
                                                    x-model="row.semester"
                                                    class="w-full rounded-md border-slate-300 text-xs shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-1.5">
                                                <option value="Genap 2526">Genap 25/26</option>
                                                <option value="Ganjil 2526">Ganjil 25/26</option>
                                                <option value="Genap 2425">Genap 24/25</option>
                                                <option value="Ganjil 2425">Ganjil 24/25</option>
                                                <option value="Genap 2324">Genap 23/24</option>
                                                <option value="Ganjil 2324">Ganjil 23/24</option>
                                                <option value="Genap 2223">Genap 22/23</option>
                                                <option value="Ganjil 2223">Ganjil 22/23</option>
                                            </select>
                                        </div>
                                        {{-- NA --}}
                                        <div class="col-span-2">
                                            <input type="number" :name="'grades[' + index + '][na]'"
                                                   x-model="row.na"
                                                   min="0" max="100" step="0.5"
                                                   placeholder="0–100"
                                                   class="w-full rounded-md border-slate-300 text-xs shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-1.5">
                                        </div>
                                        {{-- NH --}}
                                        <div class="col-span-2">
                                            <select :name="'grades[' + index + '][nh]'"
                                                    x-model="row.nh"
                                                    class="w-full rounded-md border-slate-300 text-xs shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-1.5">
                                                <option>A</option>
                                                <option>B+</option>
                                                <option>B</option>
                                                <option>C+</option>
                                                <option>C</option>
                                                <option>D+</option>
                                                <option>D</option>
                                                <option>E</option>
                                            </select>
                                        </div>
                                        {{-- Threshold NA (opsional) --}}
                                        <div class="col-span-2">
                                            <input type="number" :name="'grades[' + index + '][threshold_na]'"
                                                   x-model="row.threshold_na"
                                                   min="0" max="100" step="0.5"
                                                   placeholder="kosong = riil"
                                                   class="w-full rounded-md border-amber-200 bg-amber-50 text-xs shadow-sm focus:ring-amber-400 focus:border-amber-400 py-1.5 placeholder-amber-300">
                                        </div>
                                        {{-- Hapus --}}
                                        <div class="col-span-1 flex justify-center pt-1">
                                            <button type="button" @click="removeRow(index)"
                                                    class="text-slate-400 hover:text-red-500 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="rows.length === 0">
                                    <p class="text-xs text-slate-400 italic py-2">
                                        Klik kode matkul di atas atau tombol "+ Tambah Baris" untuk menambah nilai.
                                    </p>
                                </template>
                            </div>

                            <button type="button" @click="addRow()"
                                    class="mt-2 text-xs text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Tambah Baris
                            </button>

                            @error('grades')
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit"
                                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors">
                            Uji Sekarang
                        </button>
                    </form>
                </div>

                {{-- ===== HASIL ===== --}}
                <div class="space-y-4">
                    @if (isset($result))
                        @php
                            $decision = $result->decision;
                            $badgeClass = match($decision) {
                                'lama' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                'baru' => 'bg-blue-100 text-blue-800 border-blue-300',
                                default => 'bg-slate-100 text-slate-600 border-slate-300',
                            };
                            $badgeLabel = match($decision) {
                                'lama' => 'Eligible — Adendum PKS Lama',
                                'baru' => 'Eligible — PKS Baru',
                                default => 'Tidak Eligible',
                            };
                        @endphp

                        <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-100">
                                <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Hasil Evaluasi</h3>
                            </div>
                            <div class="p-6 space-y-4">

                                {{-- Decision badge --}}
                                <div>
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-lg border text-sm font-semibold {{ $badgeClass }}">
                                        {{ $badgeLabel }}
                                    </span>
                                </div>

                                {{-- Flags --}}
                                <div class="flex gap-3 text-xs">
                                    <span class="flex items-center gap-1 {{ $result->eligibleLama ? 'text-emerald-700' : 'text-slate-400' }}">
                                        @if ($result->eligibleLama)
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        @else
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                        @endif
                                        eligible_lama
                                    </span>
                                    <span class="flex items-center gap-1 {{ $result->eligibleBaru ? 'text-blue-700' : 'text-slate-400' }}">
                                        @if ($result->eligibleBaru)
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        @else
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                        @endif
                                        eligible_baru
                                    </span>
                                </div>

                                {{-- Reason --}}
                                <div class="rounded-lg bg-slate-50 border border-slate-200 px-4 py-3 text-sm text-slate-700">
                                    {{ $result->reason }}
                                </div>

                                {{-- Component grades --}}
                                @if ($result->decidingComponents())
                                    @php $components = $result->decidingComponents(); @endphp
                                    <div>
                                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">
                                            Komponen penentu
                                        </p>
                                        <div class="rounded-lg border border-slate-200 overflow-hidden">
                                            <table class="w-full text-xs">
                                                <thead class="bg-slate-50">
                                                    <tr>
                                                        <th class="text-left px-3 py-2 text-slate-500 font-semibold">Kode</th>
                                                        <th class="text-left px-3 py-2 text-slate-500 font-semibold">Matkul</th>
                                                        <th class="text-center px-3 py-2 text-slate-500 font-semibold">SKS</th>
                                                        <th class="text-center px-3 py-2 text-slate-500 font-semibold">NA</th>
                                                        <th class="text-center px-3 py-2 text-slate-500 font-semibold">NH</th>
                                                        <th class="text-center px-3 py-2 text-slate-500 font-semibold">Bobot</th>
                                                        @if ($decision === 'baru')
                                                            <th class="text-center px-3 py-2 text-slate-500 font-semibold">Threshold</th>
                                                            <th class="text-center px-3 py-2 text-slate-500 font-semibold">Lolos?</th>
                                                        @endif
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($components as $c)
                                                        @php
                                                            $passesThreshold = $decision === 'baru'
                                                                && $c['threshold_na'] !== null
                                                                && $c['na'] >= $c['threshold_na'];
                                                        @endphp
                                                        <tr class="border-t border-slate-100">
                                                            <td class="px-3 py-2 font-mono text-slate-700">{{ $c['course_code'] }}</td>
                                                            <td class="px-3 py-2 text-slate-600">{{ $c['course_name'] }}</td>
                                                            <td class="px-3 py-2 text-center text-slate-600">{{ $c['sks'] }}</td>
                                                            <td class="px-3 py-2 text-center font-semibold text-slate-800">{{ $c['na'] }}</td>
                                                            <td class="px-3 py-2 text-center text-slate-600">{{ $c['nh'] }}</td>
                                                            <td class="px-3 py-2 text-center text-slate-600">{{ $c['grade_point'] }}</td>
                                                            @if ($decision === 'baru')
                                                                <td class="px-3 py-2 text-center text-slate-500">
                                                                    {{ $c['threshold_na'] !== null ? $c['threshold_na'] : '—' }}
                                                                </td>
                                                                <td class="px-3 py-2 text-center">
                                                                    @if ($c['threshold_na'] === null)
                                                                        <span class="text-slate-400">—</span>
                                                                    @elseif ($passesThreshold)
                                                                        <span class="text-emerald-600 font-semibold">✓</span>
                                                                    @else
                                                                        <span class="text-red-500 font-semibold">✗</span>
                                                                    @endif
                                                                </td>
                                                            @endif
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        {{-- Weighted average --}}
                                        @php
                                            $totalWeighted = collect($components)->sum(fn($c) => $c['grade_point'] * $c['sks']);
                                            $totalSks = collect($components)->sum('sks');
                                            $avg = $totalSks > 0 ? round($totalWeighted / $totalSks, 4) : 0;
                                        @endphp
                                        <p class="text-xs text-slate-500 mt-2">
                                            Rata-rata tertimbang SKS:
                                            <span class="font-semibold {{ $avg > 3.5 ? 'text-emerald-700' : 'text-slate-700' }}">
                                                {{ $avg }}
                                            </span>
                                            <span class="{{ $avg > 3.5 ? 'text-emerald-600' : 'text-slate-400' }}">
                                                {{ $avg > 3.5 ? '(> 3.5 ✓)' : '(≤ 3.5 ✗)' }}
                                            </span>
                                        </p>
                                    </div>
                                @endif

                                {{-- All component grades (non-deciding) --}}
                                @if (count($result->componentGrades) > 1 || (count($result->componentGrades) === 1 && !$result->decidingComponents()))
                                    <details class="text-xs">
                                        <summary class="cursor-pointer text-slate-400 hover:text-slate-600">
                                            Lihat semua set yang dievaluasi ({{ count($result->componentGrades) }} set)
                                        </summary>
                                        <pre class="mt-2 bg-slate-50 rounded p-3 text-slate-600 overflow-auto text-[10px] leading-relaxed">{{ json_encode($result->componentGrades, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </details>
                                @endif

                            </div>
                        </div>

                    @else
                        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-8 text-center text-slate-400 text-sm">
                            <svg class="w-10 h-10 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            Hasil evaluasi akan muncul di sini setelah form disubmit.
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>

    <script>
    function eligibilityTest() {
        const d = window._elig;
        return {
            moduleCode:      d.initModule,
            prodi:           d.initProdi,
            moduleCourseMap: d.moduleCourseMap,
            allCourses:      d.allCourses,
            rows: d.initGrades.length > 0
                ? d.initGrades.map(g => ({ course_code: g.course_code, na: g.na, nh: g.nh, semester: g.semester, threshold_na: g.threshold_na ?? '' }))
                : [],

            get currentModuleCourses() {
                if (!this.moduleCode || !this.prodi) return {};
                return this.moduleCourseMap[this.moduleCode]?.[this.prodi] ?? {};
            },

            addCourse(code) {
                this.rows.push({ course_code: code, na: '', nh: 'A', semester: 'Genap 2223', threshold_na: '' });
            },

            addRow() {
                this.rows.push({ course_code: '', na: '', nh: 'A', semester: 'Genap 2223', threshold_na: '' });
            },

            removeRow(index) {
                this.rows.splice(index, 1);
            },
        };
    }
    </script>
</x-app-layout>
