<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Admin — Mahasiswa') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg p-4 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="p-3">No Induk</th>
                            <th class="p-3">Nama</th>
                            <th class="p-3">Prodi</th>
                            <th class="p-3">Disetujui</th>
                            <th class="p-3">Pending</th>
                            <th class="p-3">Ditolak</th>
                            <th class="p-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $student)
                            <tr class="border-t border-slate-100">
                                <td class="p-3">{{ $student->no_induk }}</td>
                                <td class="p-3">{{ $student->nama }}</td>
                                <td class="p-3">{{ $student->prodi }}</td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-medium">{{ $student->approved_count }}</span>
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-xs font-medium">{{ $student->pending_count }}</span>
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 text-xs font-medium">{{ $student->rejected_count }}</span>
                                </td>
                                <td class="p-3 text-right">
                                    <a href="{{ route('admin.students.show', $student) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $students->links() }}
        </div>
    </div>
</x-app-layout>
