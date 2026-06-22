<x-app-layout>
    <x-slot name="header">
        <h2 class="font-heading font-semibold text-xl text-slate-800 leading-tight">Cek Eligibility</h2>
    </x-slot>

    @php
        $allRows      = $eligible->concat($notEligible);
        $countAll     = $allRows->count();
        $countWeb     = $allRows->where('submission_status', 'web')->count();
        $countManual  = $allRows->where('submission_status', 'manual')->count();
        $countReg     = $allRows->where('submission_status', 'registered')->count();
        $countUnreg   = $allRows->where('submission_status', 'unregistered')->count();

        // Statuses that actually appear in each section (for empty-state detection)
        $eligibleStatuses    = $eligible->pluck('submission_status')->unique()->values();
        $notEligibleStatuses = $notEligible->pluck('submission_status')->unique()->values();

        // Export count: eligible + belum diajukan (registered or unregistered)
        $exportCount = $eligible->whereIn('submission_status', ['registered', 'unregistered'])->count();
    @endphp

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6"
         x-data="{
             filter: 'all',
             eligibleStatuses: @js($eligibleStatuses),
             notEligibleStatuses: @js($notEligibleStatuses),
             hasEligible()    { return this.filter === 'all' || this.eligibleStatuses.includes(this.filter); },
             hasNotEligible() { return this.filter === 'all' || this.notEligibleStatuses.includes(this.filter); },
         }">

        {{-- Module tabs --}}
        <div class="flex flex-wrap gap-2">
            @foreach ($modules as $mod)
                <a href="{{ route('admin.eligibility.show', $mod->code) }}"
                   class="px-4 py-2 rounded-xl text-sm font-semibold transition-colors
                       {{ $paiModule->id === $mod->id
                           ? 'bg-indigo-600 text-white shadow-sm'
                           : 'bg-white border border-slate-200 text-slate-600 hover:border-indigo-300 hover:text-indigo-600' }}">
                    {{ $mod->code }}
                    <span class="ml-1 text-xs {{ $paiModule->id === $mod->id ? 'opacity-75' : 'text-slate-400' }}">{{ $mod->name }}</span>
                </a>
            @endforeach
        </div>

        {{-- Stats bar --}}
        <div class="flex flex-wrap items-center gap-6 bg-white border border-slate-200 rounded-2xl px-6 py-4">
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Modul</p>
                <p class="font-heading font-bold text-slate-900 text-lg">{{ $paiModule->code }} — {{ $paiModule->name }}</p>
            </div>
            <div class="h-10 w-px bg-slate-200 hidden sm:block"></div>
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Eligible</p>
                <p class="font-bold text-emerald-600 text-2xl">{{ $eligible->count() }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Belum Eligible</p>
                <p class="font-bold text-slate-400 text-2xl">{{ $notEligible->count() }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total dari Nilai</p>
                <p class="font-bold text-slate-700 text-2xl">{{ $countAll }}</p>
            </div>
            <div class="h-10 w-px bg-slate-200 hidden sm:block"></div>
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Persentil PKS Baru</p>
                <p class="font-bold text-indigo-600 text-lg">{{ $paiModule->percentile }}%</p>
            </div>
            <div class="ml-auto flex items-center gap-3 flex-wrap">
                @if ($manualTotal > 0)
                    <span class="text-xs text-teal-700 bg-teal-50 border border-teal-200 rounded-lg px-3 py-1.5">
                        {{ $manualTotal }} entri manual
                    </span>
                @endif
                <a href="{{ route('admin.manual-submissions.create') }}"
                   class="text-xs bg-white border border-slate-200 hover:border-indigo-300 text-slate-600 hover:text-indigo-600 rounded-lg px-3 py-1.5 font-medium transition-colors">
                    Upload Pengajuan Manual
                </a>
            </div>
        </div>

        {{-- Filter + Export bar --}}
        <div class="flex flex-wrap items-center gap-3">
            {{-- Filter chips --}}
            <span class="text-xs text-slate-500 font-medium shrink-0">Filter:</span>

            @php
                $chips = [
                    ['key' => 'all',          'label' => 'Semua',                          'count' => $countAll,    'active' => 'bg-slate-800 text-white border-transparent',   'idle' => 'bg-white border-slate-200 text-slate-600 hover:border-slate-400'],
                    ['key' => 'web',          'label' => 'Diajukan Web',                   'count' => $countWeb,    'active' => 'bg-indigo-600 text-white border-transparent',   'idle' => 'bg-indigo-50 border-indigo-200 text-indigo-700 hover:bg-indigo-100'],
                    ['key' => 'manual',       'label' => 'Diajukan Manual',                'count' => $countManual, 'active' => 'bg-teal-600 text-white border-transparent',     'idle' => 'bg-teal-50 border-teal-200 text-teal-700 hover:bg-teal-100'],
                    ['key' => 'registered',   'label' => 'Belum Diajukan · Sudah Register', 'count' => $countReg,   'active' => 'bg-amber-500 text-white border-transparent',    'idle' => 'bg-amber-50 border-amber-200 text-amber-700 hover:bg-amber-100'],
                    ['key' => 'unregistered', 'label' => 'Belum Diajukan · Belum Register', 'count' => $countUnreg, 'active' => 'bg-slate-600 text-white border-transparent',   'idle' => 'bg-slate-100 border-slate-200 text-slate-600 hover:bg-slate-200'],
                ];
            @endphp

            @foreach ($chips as $chip)
                @if ($chip['count'] > 0 || $chip['key'] === 'all')
                    <button @click="filter = '{{ $chip['key'] }}'"
                        :class="filter === '{{ $chip['key'] }}' ? '{{ $chip['active'] }}' : '{{ $chip['idle'] }}'"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border transition-colors">
                        {{ $chip['label'] }}
                        <span class="opacity-75">{{ $chip['count'] }}</span>
                    </button>
                @endif
            @endforeach

            {{-- Export buttons --}}
            <div class="ml-auto flex items-center gap-2 flex-wrap">
                @if ($exportCount > 0)
                    <a href="{{ route('admin.eligibility.export', $paiModule->code) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-semibold transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Export {{ $paiModule->code }} ({{ $exportCount }})
                    </a>
                @endif
                <a href="{{ route('admin.eligibility.export-all') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold transition-colors shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Export Semua Modul
                </a>
            </div>
        </div>

        {{-- Eligible table --}}
        @if ($eligible->isNotEmpty())
            <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-sm font-semibold">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        Eligible — {{ $eligible->count() }} mahasiswa
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-xs font-medium text-slate-500 uppercase tracking-wide">
                                <th class="text-left px-5 py-3 min-w-[200px]">Mahasiswa</th>
                                <th class="text-left px-4 py-3 w-48">Status Pengajuan</th>
                                <th class="text-left px-4 py-3 w-36">Skema Eligible</th>
                                <th class="text-left px-4 py-3">Komponen Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($eligible as $row)
                                @php
                                    $result   = $row['result'];
                                    $student  = $row['student'];
                                    $deciding = $result->decidingCurriculum;
                                    $comp     = $deciding ? ($result->componentGrades[$deciding] ?? null) : null;
                                @endphp
                                <tr class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors"
                                    x-show="filter === 'all' || filter === '{{ $row['submission_status'] }}'">

                                    {{-- Mahasiswa --}}
                                    <td class="px-5 py-4">
                                        <div class="font-medium text-slate-900">{{ $student->nama }}</div>
                                        <div class="text-xs text-slate-400 font-mono mt-0.5">{{ $student->no_induk }}</div>
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                                {{ ($row['inferred_prodi'] ?? $student->prodi) === 'S1 Matematika'
                                                    ? 'bg-sky-50 text-sky-700' : 'bg-violet-50 text-violet-700' }}">
                                                {{ $row['inferred_prodi'] ?? $student->prodi }}
                                            </span>
                                            @if (! $row['registered'])
                                                <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-orange-50 text-orange-600">Belum Register</span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Status pengajuan --}}
                                    <td class="px-4 py-4">
                                        @if ($row['submission_status'] === 'web')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-semibold">
                                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 shrink-0"></span>Diajukan via Web
                                            </span>
                                        @elseif ($row['submission_status'] === 'manual')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-teal-50 text-teal-700 text-xs font-semibold">
                                                <span class="w-1.5 h-1.5 rounded-full bg-teal-500 shrink-0"></span>Diajukan Manual
                                            </span>
                                        @elseif ($row['submission_status'] === 'registered')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-semibold">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0"></span>Belum Diajukan
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 text-xs font-semibold">
                                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 shrink-0"></span>Belum Register
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Skema --}}
                                    <td class="px-4 py-4">
                                        @if ($result->decision === 'baru')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-bold">PKS Baru</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-bold">PKS Lama</span>
                                        @endif
                                        @if ($deciding)
                                            <div class="text-xs text-slate-400 mt-1">kurikulum <span class="font-medium">{{ $deciding }}</span></div>
                                        @endif
                                    </td>

                                    {{-- Komponen nilai --}}
                                    <td class="px-4 py-4">
                                        @if ($comp)
                                            <div class="space-y-1.5">
                                                @foreach ($comp['courses'] as $c)
                                                    <div class="flex items-center gap-2 text-xs">
                                                        <span class="font-mono text-slate-400 w-20 shrink-0">{{ $c['course_code'] }}</span>
                                                        <span class="text-slate-600 truncate max-w-[160px]" title="{{ $c['course_name'] }}">{{ $c['course_name'] }}</span>
                                                        <span class="text-slate-400 shrink-0">{{ $c['sks'] }} SKS</span>
                                                        @if ($result->decision === 'baru')
                                                            <span class="ml-auto shrink-0 font-semibold {{ $c['na'] >= ($c['threshold_na'] ?? 999) ? 'text-emerald-700' : 'text-red-600' }}">
                                                                NA {{ number_format($c['na'], 1) }}
                                                            </span>
                                                            @if ($c['threshold_na'] !== null)
                                                                <span class="shrink-0 text-slate-400">≥ {{ number_format($c['threshold_na'], 1) }}</span>
                                                                @if ($c['na'] >= $c['threshold_na'])
                                                                    <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                                @else
                                                                    <svg class="w-3.5 h-3.5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                                @endif
                                                            @endif
                                                        @else
                                                            <span class="ml-auto shrink-0 font-bold text-amber-700">{{ $c['nh'] }}</span>
                                                            <span class="shrink-0 text-slate-400">= {{ number_format($c['grade_point'], 1) }}</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                                @if ($result->decision === 'lama')
                                                    <div class="mt-2 pt-2 border-t border-slate-100 text-xs flex items-center gap-2">
                                                        <span class="text-slate-400">Rata-rata tertimbang:</span>
                                                        <span class="font-bold text-amber-700">{{ number_format($comp['weighted_average'], 4) }}</span>
                                                        <span class="text-slate-400">&gt; 3,5</span>
                                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-slate-400 text-xs italic">— tidak ada data komponen —</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach

                            {{-- Empty state when all eligible rows are filtered out --}}
                            <tr x-show="!hasEligible()">
                                <td colspan="4" class="px-5 py-8 text-center text-slate-400 text-sm">
                                    Tidak ada mahasiswa eligible yang cocok dengan filter ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="bg-white border border-slate-200 rounded-2xl p-10 text-center text-slate-400">
                <p class="text-lg font-medium">Belum ada mahasiswa eligible untuk modul ini.</p>
                <p class="text-sm mt-1">Pastikan nilai sudah diimport dan threshold sudah dihitung.</p>
            </div>
        @endif

        {{-- Not eligible — collapsible --}}
        @if ($notEligible->isNotEmpty())
            <div x-data="{ open: false }" class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
                <button @click="open = !open"
                    class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-slate-50 transition-colors">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 text-slate-500 text-sm font-semibold">
                        Belum Eligible — {{ $notEligible->count() }} mahasiswa
                    </span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform" :class="{ 'rotate-180': open }"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open" class="border-t border-slate-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-xs font-medium text-slate-500 uppercase tracking-wide">
                                    <th class="text-left px-5 py-3 min-w-[200px]">Mahasiswa</th>
                                    <th class="text-left px-4 py-3 w-48">Status Pengajuan</th>
                                    <th class="text-left px-4 py-3">Alasan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($notEligible as $row)
                                    <tr class="border-b border-slate-100"
                                        x-show="filter === 'all' || filter === '{{ $row['submission_status'] }}'">
                                        <td class="px-5 py-3">
                                            <div class="font-medium text-slate-700">{{ $row['student']->nama }}</div>
                                            <div class="text-xs text-slate-400 font-mono">{{ $row['student']->no_induk }}</div>
                                            <div class="flex flex-wrap gap-1 mt-1">
                                                <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                                    {{ ($row['inferred_prodi'] ?? $row['student']->prodi) === 'S1 Matematika'
                                                        ? 'bg-sky-50 text-sky-700' : 'bg-violet-50 text-violet-700' }}">
                                                    {{ $row['inferred_prodi'] ?? $row['student']->prodi }}
                                                </span>
                                                @if (! $row['registered'])
                                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-orange-50 text-orange-600">Belum Register</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($row['submission_status'] === 'web')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-semibold">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 shrink-0"></span>Diajukan via Web
                                                </span>
                                            @elseif ($row['submission_status'] === 'manual')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-teal-50 text-teal-700 text-xs font-semibold">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-teal-500 shrink-0"></span>Diajukan Manual
                                                </span>
                                            @elseif ($row['submission_status'] === 'registered')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-semibold">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0"></span>Belum Diajukan
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 text-xs font-semibold">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400 shrink-0"></span>Belum Register
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-xs text-slate-500">{{ $row['result']->reason }}</td>
                                    </tr>
                                @endforeach
                                <tr x-show="!hasNotEligible()">
                                    <td colspan="3" class="px-5 py-6 text-center text-slate-400 text-sm">
                                        Tidak ada yang cocok dengan filter ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-app-layout>
