<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.grades.import.create') }}"
               class="text-slate-400 hover:text-slate-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Distribusi Nilai — {{ $course->code }}
                </h2>
                <p class="text-sm text-slate-500 mt-0.5">{{ $course->name }} &middot; {{ $semester }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @php
                $thresholdNa  = $threshold?->threshold_na !== null ? (float) $threshold->threshold_na : null;
                $total        = $grades->count();
                $passingCount = $thresholdNa !== null ? $grades->where('na', '>=', $thresholdNa)->count() : 0;
                $failingCount = $total - $passingCount;
                $passingPct   = $total > 0 && $thresholdNa !== null ? round($passingCount / $total * 100, 1) : null;
            @endphp

            {{-- Summary cards --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
                    <p class="text-2xl font-bold text-slate-800">{{ $total }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">Total Mahasiswa</p>
                </div>

                @if ($thresholdNa !== null)
                    <div class="bg-emerald-50 rounded-xl border border-emerald-200 p-4 text-center">
                        <p class="text-2xl font-bold text-emerald-700">{{ $passingCount }}</p>
                        <p class="text-xs text-emerald-600 mt-0.5">
                            Lolos Percentile
                            @if ($passingPct !== null)
                                <span class="text-emerald-400">({{ $passingPct }}%)</span>
                            @endif
                        </p>
                    </div>
                    <div class="bg-rose-50 rounded-xl border border-rose-200 p-4 text-center">
                        <p class="text-2xl font-bold text-rose-700">{{ $failingCount }}</p>
                        <p class="text-xs text-rose-600 mt-0.5">
                            Tidak Lolos
                            @if ($passingPct !== null)
                                <span class="text-rose-400">({{ round(100 - $passingPct, 1) }}%)</span>
                            @endif
                        </p>
                    </div>
                    <div class="bg-amber-50 rounded-xl border border-amber-200 p-4 text-center">
                        <p class="text-2xl font-bold text-amber-700">{{ $thresholdNa }}</p>
                        <p class="text-xs text-amber-600 mt-0.5">Threshold NA</p>
                    </div>
                @else
                    <div class="col-span-3 bg-amber-50 rounded-xl border border-amber-200 p-4 flex items-center gap-3 text-sm text-amber-800">
                        <svg class="w-5 h-5 flex-shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        Threshold belum dihitung — import nilai lebih banyak atau jalankan ulang threshold computation.
                    </div>
                @endif
            </div>

            {{-- Grade table --}}
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="text-center px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide w-12">#</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">No Induk</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Nama</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">NA</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">NH</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Bobot</th>
                            @if ($thresholdNa !== null)
                                <th class="text-center px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide w-24">Status</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @if ($total === 0)
                            <tr>
                                <td colspan="7" class="text-center py-12 text-slate-400 text-sm">
                                    Tidak ada data nilai untuk periode ini.
                                </td>
                            </tr>
                        @else
                            @php
                                $rank = 0;
                                $thresholdRowInserted = false;
                            @endphp

                            @foreach ($grades as $grade)
                                @php
                                    $rank++;
                                    $passes = $thresholdNa !== null && (float) $grade->na >= $thresholdNa;
                                    $insertSeparator = $thresholdNa !== null && ! $thresholdRowInserted && ! $passes;
                                    if ($insertSeparator) { $thresholdRowInserted = true; }
                                @endphp

                                @if ($insertSeparator)
                                    <tr class="bg-slate-100 border-y border-slate-300">
                                        <td colspan="{{ $thresholdNa !== null ? 7 : 6 }}" class="py-1.5 px-4">
                                            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                                                <div class="flex-1 h-px bg-slate-300"></div>
                                                <span>Batas Threshold: {{ $thresholdNa }}</span>
                                                <div class="flex-1 h-px bg-slate-300"></div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif

                                <tr class="border-b border-slate-100 transition-colors
                                    @if ($thresholdNa !== null)
                                        {{ $passes ? 'bg-emerald-50 hover:bg-emerald-100/70' : 'bg-rose-50 hover:bg-rose-100/70' }}
                                    @else
                                        hover:bg-slate-50
                                    @endif">
                                    <td class="px-4 py-2.5 text-center text-slate-400 text-xs font-mono">{{ $rank }}</td>
                                    <td class="px-4 py-2.5 font-mono text-xs text-slate-600">{{ $grade->no_induk }}</td>
                                    <td class="px-4 py-2.5 text-slate-800">{{ $grade->nama }}</td>
                                    <td class="px-4 py-2.5 text-center font-bold
                                        @if ($thresholdNa !== null)
                                            {{ $passes ? 'text-emerald-700' : 'text-rose-700' }}
                                        @else
                                            text-slate-800
                                        @endif">
                                        {{ (float) $grade->na }}
                                    </td>
                                    <td class="px-4 py-2.5 text-center text-slate-600">{{ $grade->nh }}</td>
                                    <td class="px-4 py-2.5 text-center text-slate-500">{{ (float) $grade->grade_point }}</td>
                                    @if ($thresholdNa !== null)
                                        <td class="px-4 py-2.5 text-center">
                                            @if ($passes)
                                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                    Lolos
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-rose-600">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                    Tidak
                                                </span>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach

                            {{-- Kalau semua lolos, pemisah threshold belum disisipkan --}}
                            @if ($thresholdNa !== null && ! $thresholdRowInserted && $total > 0)
                                <tr class="bg-slate-100 border-y border-slate-300">
                                    <td colspan="7" class="py-1.5 px-4">
                                        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                                            <div class="flex-1 h-px bg-slate-300"></div>
                                            <span>Batas Threshold: {{ $thresholdNa }} — semua mahasiswa lolos</span>
                                            <div class="flex-1 h-px bg-slate-300"></div>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endif
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
