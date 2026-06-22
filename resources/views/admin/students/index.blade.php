<x-app-layout>
    <x-slot name="header">
        <h2 class="font-heading font-semibold text-xl text-slate-900 leading-tight">
            Dashboard Admin &mdash; Mahasiswa
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="flex items-center p-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200" role="alert">
                    <svg class="shrink-0 inline w-4 h-4 me-3" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                    </svg>
                    {{ session('status') }}
                </div>
            @endif

            {{-- Metric cards --}}
            <div class="grid sm:grid-cols-3 gap-4">
                <div class="bg-gradient-to-br from-violet-500 to-indigo-600 rounded-2xl p-5 text-white shadow">
                    <p class="text-violet-100 text-xs font-medium uppercase tracking-widest">Total Mahasiswa</p>
                    <p class="text-4xl font-heading font-bold mt-1">{{ $metrics['totalStudents'] }}</p>
                    <p class="text-violet-200 text-xs mt-1">Sudah terdaftar di sistem</p>
                </div>
                <div class="bg-gradient-to-br from-amber-400 to-orange-500 rounded-2xl p-5 text-white shadow">
                    <p class="text-amber-100 text-xs font-medium uppercase tracking-widest">Menunggu Review</p>
                    <p class="text-4xl font-heading font-bold mt-1">{{ $metrics['totalPending'] }}</p>
                    <p class="text-amber-100 text-xs mt-1">Perlu ditindaklanjuti</p>
                </div>
                <div class="bg-gradient-to-br from-emerald-400 to-teal-600 rounded-2xl p-5 text-white shadow">
                    <p class="text-emerald-100 text-xs font-medium uppercase tracking-widest">Total Disetujui</p>
                    <p class="text-4xl font-heading font-bold mt-1">{{ $metrics['totalApproved'] }}</p>
                    <p class="text-emerald-100 text-xs mt-1">Sepanjang waktu</p>
                </div>
            </div>

            {{-- Table card --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

                {{-- Table header / search bar --}}
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-5 py-4 bg-gradient-to-r from-indigo-600 via-violet-600 to-purple-600">
                    <div>
                        <h3 class="text-base font-heading font-semibold text-white">Daftar Mahasiswa</h3>
                        <p class="text-indigo-200 text-xs mt-0.5">
                            {{ $students->total() }} mahasiswa{{ $search ? ' cocok dengan pencarian "' . $search . '"' : ' terdaftar' }}
                        </p>
                    </div>
                    <form method="GET" action="{{ route('admin.students.index') }}" class="flex gap-2">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-4 h-4 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input
                                type="text"
                                name="search"
                                value="{{ $search }}"
                                placeholder="Cari nama atau NIM..."
                                class="pl-9 pr-3 py-2 text-sm bg-white/10 border border-white/20 text-white placeholder-indigo-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-white/40 w-52"
                            >
                        </div>
                        <button type="submit"
                            class="bg-white text-indigo-700 hover:bg-indigo-50 text-sm font-semibold px-4 py-2 rounded-xl transition-colors">
                            Cari
                        </button>
                        @if ($search)
                            <a href="{{ route('admin.students.index') }}"
                                class="flex items-center text-white/70 hover:text-white text-sm px-2 py-2 rounded-xl transition-colors" title="Reset">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </a>
                        @endif
                    </form>
                </div>

                {{-- Flowbite table --}}
                @if ($students->isEmpty())
                    <div class="py-16 text-center">
                        <svg class="w-10 h-10 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <p class="text-sm font-medium text-slate-500">
                            {{ $search ? 'Tidak ada mahasiswa yang cocok.' : 'Belum ada mahasiswa terdaftar.' }}
                        </p>
                        @if ($search)
                            <a href="{{ route('admin.students.index') }}" class="text-indigo-600 hover:text-indigo-800 text-sm mt-1 inline-block">
                                Lihat semua mahasiswa
                            </a>
                        @endif
                    </div>
                @else
                    <div class="relative overflow-x-auto">
                        <table class="w-full text-sm text-left text-slate-600">
                            <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th scope="col" class="px-6 py-3 font-semibold tracking-wider">No Induk</th>
                                    <th scope="col" class="px-6 py-3 font-semibold tracking-wider">Nama</th>
                                    <th scope="col" class="px-6 py-3 font-semibold tracking-wider">Prodi</th>
                                    <th scope="col" class="px-4 py-3 font-semibold tracking-wider text-center">
                                        <span class="inline-flex items-center gap-1">
                                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>Disetujui
                                        </span>
                                    </th>
                                    <th scope="col" class="px-4 py-3 font-semibold tracking-wider text-center">
                                        <span class="inline-flex items-center gap-1">
                                            <span class="w-2 h-2 rounded-full bg-amber-400"></span>Pending
                                        </span>
                                    </th>
                                    <th scope="col" class="px-4 py-3 font-semibold tracking-wider text-center">
                                        <span class="inline-flex items-center gap-1">
                                            <span class="w-2 h-2 rounded-full bg-rose-400"></span>Ditolak
                                        </span>
                                    </th>
                                    <th scope="col" class="px-6 py-3 font-semibold tracking-wider text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($students as $i => $student)
                                    <tr class="border-b border-slate-100 {{ $i % 2 === 0 ? 'bg-white' : 'bg-slate-50/40' }} hover:bg-indigo-50/30 transition-colors group">
                                        <td class="px-6 py-4">
                                            <span class="font-mono text-xs bg-slate-100 text-slate-500 px-2 py-0.5 rounded-md">
                                                {{ $student->no_induk }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0
                                                    {{ ['bg-indigo-500','bg-violet-500','bg-purple-500','bg-fuchsia-500','bg-pink-500','bg-rose-500','bg-orange-500','bg-amber-500','bg-teal-500','bg-emerald-500'][$loop->index % 10] }}">
                                                    {{ strtoupper(substr($student->nama, 0, 1)) }}
                                                </div>
                                                <span class="font-semibold text-slate-800">{{ $student->nama }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-500 text-xs">{{ $student->prodi }}</td>
                                        <td class="px-4 py-4 text-center">
                                            @if ($student->approved_count > 0)
                                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold ring-2 ring-emerald-200">
                                                    {{ $student->approved_count }}
                                                </span>
                                            @else
                                                <span class="text-slate-300">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            @if ($student->pending_count > 0)
                                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-100 text-amber-700 text-xs font-bold ring-2 ring-amber-200">
                                                    {{ $student->pending_count }}
                                                </span>
                                            @else
                                                <span class="text-slate-300">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            @if ($student->rejected_count > 0)
                                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-rose-100 text-rose-700 text-xs font-bold ring-2 ring-rose-200">
                                                    {{ $student->rejected_count }}
                                                </span>
                                            @else
                                                <span class="text-slate-300">—</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('admin.students.show', $student) }}"
                                                    class="inline-flex items-center gap-1.5 text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-xs px-3 py-1.5 transition-colors">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                    Detail
                                                </a>
                                                @if ($student->approved_count > 0)
                                                    <a href="{{ route('admin.students.letter', $student) }}"
                                                        class="inline-flex items-center gap-1.5 text-slate-700 bg-white hover:bg-slate-50 focus:ring-4 focus:ring-slate-200 border border-slate-300 font-medium rounded-lg text-xs px-3 py-1.5 transition-colors">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                        </svg>
                                                        Surat
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/60">
                        {{ $students->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
