<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.students.index') }}" class="text-slate-400 hover:text-slate-600" title="Kembali ke daftar mahasiswa">
                &larr;
            </a>
            <h2 class="font-heading font-semibold text-xl text-slate-900 leading-tight">
                {{ __('Detail Mahasiswa') }}
            </h2>
        </div>
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

            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <p class="text-sm text-slate-500">No Induk: {{ $student->no_induk }} &middot; {{ $student->prodi }}</p>
                <h3 class="font-heading text-lg font-semibold text-slate-900">{{ $student->nama }}</h3>
            </div>

            @forelse ($submissions as $submission)
                <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-start gap-3">
                            <span class="inline-block bg-module-{{ strtolower($submission->paiModule->code) }} text-white text-xs font-semibold px-2 py-0.5 rounded-lg mt-0.5">
                                {{ $submission->paiModule->code }}
                            </span>
                            <div>
                                <h4 class="font-heading font-semibold text-slate-900">{{ $submission->paiModule->name }}</h4>
                                <p class="text-sm text-slate-500">
                                    Skema {{ $submission->scheme === 'baru' ? 'PKS Baru' : 'PKS Lama' }} &middot;
                                    Rp{{ number_format($submission->price, 0, ',', '.') }}
                                </p>
                            </div>
                        </div>
                        <x-status-badge :variant="$submission->status">{{ ucfirst($submission->status) }}</x-status-badge>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-slate-500 mb-2">Rincian matkul komponen yang dipakai buat keputusan ini:</p>
                        <table class="w-full text-sm border border-slate-200 rounded-xl overflow-hidden">
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
                    </div>

                    @if ($submission->status === 'rejected' && $submission->rejection_reason)
                        <p class="text-sm text-rose-700 bg-rose-50 border border-rose-100 rounded-xl p-3">
                            <span class="font-medium">Alasan ditolak:</span> {{ $submission->rejection_reason }}
                        </p>
                    @endif

                    @if ($submission->status === 'approved' || $submission->status === 'rejected')
                        <p class="text-xs text-slate-400">
                            Direview oleh {{ $submission->reviewedBy?->name ?? '—' }}
                            @if ($submission->reviewed_at)
                                pada {{ $submission->reviewed_at->translatedFormat('d M Y, H:i') }}
                            @endif
                        </p>
                    @endif

                    @if ($submission->status === 'pending')
                        <div class="flex items-start gap-3 pt-2 border-t border-slate-100">
                            <form method="POST" action="{{ route('admin.submissions.approve', $submission) }}" class="pt-4">
                                @csrf
                                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl px-4 py-2 text-sm font-medium">
                                    Setujui
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.submissions.reject', $submission) }}" class="flex-1 space-y-2 pt-4">
                                @csrf
                                <textarea name="rejection_reason" rows="2" placeholder="Alasan penolakan (wajib diisi kalau mau tolak)" class="w-full text-sm border-slate-300 rounded-xl shadow-sm">{{ old('rejection_reason') }}</textarea>
                                <x-input-error :messages="$errors->get('rejection_reason')" />
                                <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white rounded-xl px-4 py-2 text-sm font-medium">
                                    Tolak
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            @empty
                <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center space-y-1">
                    <p class="text-sm font-medium text-slate-700">Belum ada pengajuan</p>
                    <p class="text-sm text-slate-500">
                        {{ $student->nama }} belum mengajukan penyetaraan modul apapun. Pastikan nilai
                        matkulnya sudah diimport supaya eligibility-nya bisa terhitung di dashboard dia.
                    </p>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
