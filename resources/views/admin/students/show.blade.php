<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Detail Mahasiswa') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

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

            <div class="bg-white rounded-lg shadow-sm p-6">
                <p class="text-sm text-slate-500">No Induk: {{ $student->no_induk }} · {{ $student->prodi }}</p>
                <h3 class="text-lg font-semibold text-slate-900">{{ $student->nama }}</h3>
            </div>

            @forelse ($submissions as $submission)
                @php
                    $statusClass = match ($submission->status) {
                        'approved' => 'bg-emerald-50 text-emerald-700',
                        'rejected' => 'bg-rose-50 text-rose-700',
                        default => 'bg-amber-50 text-amber-700',
                    };
                @endphp

                <div class="bg-white rounded-lg shadow-sm p-6 space-y-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-xs font-semibold text-slate-500">{{ $submission->paiModule->code }}</span>
                            <h4 class="font-semibold text-slate-900">{{ $submission->paiModule->name }}</h4>
                            <p class="text-sm text-slate-500">
                                Skema {{ $submission->scheme === 'baru' ? 'PKS Baru' : 'PKS Lama' }} ·
                                Rp{{ number_format($submission->price, 0, ',', '.') }}
                            </p>
                        </div>
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full {{ $statusClass }}">{{ ucfirst($submission->status) }}</span>
                    </div>

                    <table class="w-full text-sm border border-slate-200 rounded-lg overflow-hidden">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="text-left p-2">Matkul</th>
                                <th class="text-left p-2">SKS</th>
                                <th class="text-left p-2">NA</th>
                                <th class="text-left p-2">NH</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($submission->submissionCourses as $sc)
                                <tr class="border-t border-slate-100">
                                    <td class="p-2">{{ $sc->course->code }} - {{ $sc->course->name }}</td>
                                    <td class="p-2">{{ $sc->course->sks }}</td>
                                    <td class="p-2">{{ $sc->na }}</td>
                                    <td class="p-2">{{ $sc->nh }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if ($submission->status === 'rejected' && $submission->rejection_reason)
                        <p class="text-sm text-rose-700">Alasan ditolak: {{ $submission->rejection_reason }}</p>
                    @endif

                    @if ($submission->status === 'pending')
                        <div class="flex items-start gap-3 pt-2">
                            <form method="POST" action="{{ route('admin.submissions.approve', $submission) }}">
                                @csrf
                                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl px-4 py-2 text-sm font-medium">
                                    Setujui
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.submissions.reject', $submission) }}" class="flex-1 space-y-2">
                                @csrf
                                <textarea name="rejection_reason" rows="2" placeholder="Alasan penolakan (wajib)" class="w-full text-sm border-gray-300 rounded-md shadow-sm">{{ old('rejection_reason') }}</textarea>
                                <x-input-error :messages="$errors->get('rejection_reason')" />
                                <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white rounded-xl px-4 py-2 text-sm font-medium">
                                    Tolak
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            @empty
                <div class="bg-white rounded-lg shadow-sm p-6 text-sm text-slate-500">
                    Mahasiswa ini belum mengajukan penyetaraan modul apapun.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
