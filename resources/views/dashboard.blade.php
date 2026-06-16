<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
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

            @if (! $student)
                <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-lg p-4 text-sm">
                    Profil mahasiswa (No Induk/Prodi) belum lengkap. Hubungi admin untuk melengkapi data.
                </div>
            @else
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($cards as $card)
                        @php
                            $module = $card['module'];
                            $result = $card['result'];
                            $submission = $card['submission'];

                            // Tentukan badge & state tombol.
                            if ($submission && $submission->status === 'pending') {
                                $badgeLabel = 'Menunggu review';
                                $badgeClass = 'bg-amber-50 text-amber-700';
                                $buttonLabel = 'Sudah diajukan';
                                $buttonEnabled = false;
                            } elseif ($submission && $submission->status === 'approved') {
                                $badgeLabel = 'Disetujui';
                                $badgeClass = 'bg-emerald-50 text-emerald-700';
                                $buttonLabel = 'Disetujui';
                                $buttonEnabled = false;
                            } elseif ($submission && $submission->status === 'rejected') {
                                $badgeLabel = 'Ditolak';
                                $badgeClass = 'bg-rose-50 text-rose-700';
                                $buttonLabel = 'Ajukan ulang';
                                $buttonEnabled = $result->decision !== 'none';
                            } elseif ($result->decision === 'baru') {
                                $badgeLabel = 'Eligible (PKS Baru)';
                                $badgeClass = 'bg-emerald-50 text-emerald-700';
                                $buttonLabel = 'Ajukan Penyetaraan';
                                $buttonEnabled = true;
                            } elseif ($result->decision === 'lama') {
                                $badgeLabel = 'Eligible (PKS Lama)';
                                $badgeClass = 'bg-blue-50 text-blue-700';
                                $buttonLabel = 'Ajukan Penyetaraan';
                                $buttonEnabled = true;
                            } else {
                                $badgeLabel = 'Belum Eligible';
                                $badgeClass = 'bg-slate-100 text-slate-500';
                                $buttonLabel = 'Ajukan Penyetaraan';
                                $buttonEnabled = false;
                            }
                        @endphp

                        <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-3">
                            <div class="flex items-start justify-between">
                                <div>
                                    <span class="text-xs font-semibold text-slate-500">{{ $module->code }}</span>
                                    <h3 class="font-semibold text-slate-900">{{ $module->name }}</h3>
                                </div>
                                <span class="text-xs font-medium px-2.5 py-1 rounded-full {{ $badgeClass }}">{{ $badgeLabel }}</span>
                            </div>

                            <p class="text-sm text-slate-500">{{ $result->reason }}</p>

                            @if ($result->price)
                                <p class="text-sm font-medium text-slate-700">Rp{{ number_format($result->price, 0, ',', '.') }}</p>
                            @endif

                            <div>
                                @if ($buttonEnabled)
                                    <a href="{{ route('submissions.create', $module->code) }}"
                                       class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-4 py-2 text-sm font-medium">
                                        {{ $buttonLabel }}
                                    </a>
                                @else
                                    <span class="inline-block bg-slate-100 text-slate-400 rounded-xl px-4 py-2 text-sm font-medium cursor-not-allowed">
                                        {{ $buttonLabel }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
