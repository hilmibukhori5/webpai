<x-app-layout>
    <x-slot name="header">
        <h2 class="font-heading font-semibold text-xl text-slate-900 leading-tight">
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
                <div>
                    <h3 class="font-heading text-lg font-semibold text-slate-900">Halo, {{ $student->nama }} 👋</h3>
                    <p class="text-sm text-slate-500 mt-1 max-w-2xl">
                        Ini status eligibility kamu di tiap modul A10&ndash;A70, dihitung otomatis dari
                        nilai matkul yang sudah diinput admin (dicocokkan lewat No Induk
                        <span class="font-medium text-slate-700">{{ $student->no_induk }}</span>).
                        Kalau ada modul yang eligible, tombol "Ajukan Penyetaraan" bakal aktif.
                    </p>
                </div>

                <div class="grid sm:grid-cols-3 gap-4">
                    <x-metric-card label="Eligible" :value="$metrics['eligible']" hint="Modul yang lolos PKS Baru/Lama" />
                    <x-metric-card label="Diajukan" :value="$metrics['diajukan']" hint="Total pengajuan yang pernah dikirim" />
                    <x-metric-card label="Disetujui" :value="$metrics['disetujui']" hint="Pengajuan yang sudah di-approve admin" />
                </div>

                <div class="bg-indigo-50/60 border border-indigo-100 rounded-2xl p-5 text-sm text-slate-700 space-y-1">
                    <p class="font-medium text-slate-900">Soal skema PKS Baru &amp; PKS Lama</p>
                    <p>
                        <span class="font-medium text-emerald-700">PKS Baru</span> dicek dari percentile nilai
                        (NA) kamu dibanding semua mahasiswa lain yang pernah ambil matkul itu — harga
                        Rp550.000/modul. <span class="font-medium text-blue-700">PKS Lama</span> dicek dari
                        rata-rata bobot nilai (NH) matkul kurikulum lama — harga Rp500.000/modul. Sistem
                        otomatis pilih skema yang paling menguntungkan buat kamu; kalau belum eligible di
                        keduanya, kartu modul bakal kasih tahu alasannya.
                    </p>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($cards as $card)
                        @php
                            $module = $card['module'];
                            $result = $card['result'];
                            $submission = $card['submission'];

                            // Tentukan variant badge & state tombol.
                            if ($submission && $submission->status === 'pending') {
                                $badgeVariant = 'pending';
                                $badgeLabel = 'Menunggu review';
                                $buttonLabel = 'Sudah diajukan';
                                $buttonHref = null;
                            } elseif ($submission && $submission->status === 'approved') {
                                $badgeVariant = 'approved';
                                $badgeLabel = 'Disetujui';
                                $buttonLabel = 'Disetujui';
                                $buttonHref = null;
                            } elseif ($submission && $submission->status === 'rejected') {
                                $badgeVariant = 'rejected';
                                $badgeLabel = 'Ditolak';
                                $buttonLabel = 'Ajukan ulang';
                                $buttonHref = $result->decision !== 'none' ? route('submissions.create', $module->code) : null;
                            } elseif ($result->decision === 'baru') {
                                $badgeVariant = 'eligible-baru';
                                $badgeLabel = 'Eligible (PKS Baru)';
                                $buttonLabel = 'Ajukan Penyetaraan';
                                $buttonHref = route('submissions.create', $module->code);
                            } elseif ($result->decision === 'lama') {
                                $badgeVariant = 'eligible-lama';
                                $badgeLabel = 'Eligible (PKS Lama)';
                                $buttonLabel = 'Ajukan Penyetaraan';
                                $buttonHref = route('submissions.create', $module->code);
                            } else {
                                $badgeVariant = 'belum-eligible';
                                $badgeLabel = 'Belum Eligible';
                                $buttonLabel = 'Ajukan Penyetaraan';
                                $buttonHref = null;
                            }
                        @endphp

                        <x-module-card
                            :code="$module->code"
                            :name="$module->name"
                            :color="$card['color']"
                            :component-names="$card['componentNames']"
                            :reason="$result->reason"
                            :price="$result->price"
                        >
                            <x-slot name="badge">
                                <x-status-badge :variant="$badgeVariant">{{ $badgeLabel }}</x-status-badge>
                            </x-slot>

                            <x-slot name="footer">
                                @if ($buttonHref)
                                    <x-btn variant="primary" :href="$buttonHref">{{ $buttonLabel }}</x-btn>
                                @else
                                    <x-btn variant="disabled">{{ $buttonLabel }}</x-btn>
                                @endif
                            </x-slot>
                        </x-module-card>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
