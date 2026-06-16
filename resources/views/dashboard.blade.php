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
                <div class="grid sm:grid-cols-3 gap-4">
                    <x-metric-card label="Eligible" :value="$metrics['eligible']" />
                    <x-metric-card label="Diajukan" :value="$metrics['diajukan']" />
                    <x-metric-card label="Disetujui" :value="$metrics['disetujui']" />
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
