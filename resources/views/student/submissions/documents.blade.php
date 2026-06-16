<x-app-layout>
    <x-slot name="header">
        <h2 class="font-heading font-semibold text-xl text-slate-900 leading-tight">
            {{ __('Upload Bukti Bayar & Formulir') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg p-4 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-6">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <span class="text-xs font-semibold text-slate-500">{{ $submission->paiModule->code }}</span>
                        <h3 class="text-lg font-semibold text-slate-900">{{ $submission->paiModule->name }}</h3>
                        <p class="text-sm text-slate-500 mt-1">
                            Skema {{ $submission->scheme === 'baru' ? 'PKS Baru' : 'PKS Lama' }} &middot;
                            Rp{{ number_format($submission->price, 0, ',', '.') }}
                        </p>
                    </div>
                    <x-status-badge :variant="$submission->payment_status === 'paid' ? 'approved' : 'pending'">
                        {{ $submission->payment_status === 'paid' ? 'Lunas' : 'Belum Bayar' }}
                    </x-status-badge>
                </div>

                <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 text-sm text-slate-600">
                    Upload <strong>bukti pembayaran</strong> dan <strong>formulir permohonan penyetaraan
                    ujian</strong> yang sudah diisi & ditandatangani (file formulirnya dilampirkan di
                    email persetujuanmu). Status otomatis jadi <strong>Lunas</strong> begitu kedua file
                    ini ada — boleh diupload satu-satu atau sekaligus, dan bisa diganti lagi kapan saja
                    kalau salah upload.
                </div>

                <form method="POST" action="{{ route('submissions.documents.update', $submission) }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="bukti_pembayaran" value="Bukti Pembayaran (pdf/jpg/png, maks 5MB)" />
                        <input type="file" id="bukti_pembayaran" name="bukti_pembayaran" accept=".pdf,.jpg,.jpeg,.png"
                            class="mt-1 block w-full text-sm text-slate-600 border border-slate-300 rounded-xl shadow-sm">
                        <p class="text-xs text-slate-500 mt-1">
                            @if ($submission->bukti_pembayaran_path)
                                Sudah ada: <a href="{{ Storage::url($submission->bukti_pembayaran_path) }}" target="_blank" class="text-indigo-600 underline">lihat file</a> (upload ulang untuk mengganti).
                            @else
                                Belum diupload.
                            @endif
                        </p>
                        <x-input-error :messages="$errors->get('bukti_pembayaran')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="formulir_terisi" value="Formulir Terisi (pdf/doc/docx, maks 5MB)" />
                        <input type="file" id="formulir_terisi" name="formulir_terisi" accept=".pdf,.doc,.docx"
                            class="mt-1 block w-full text-sm text-slate-600 border border-slate-300 rounded-xl shadow-sm">
                        <p class="text-xs text-slate-500 mt-1">
                            @if ($submission->formulir_terisi_path)
                                Sudah ada: <a href="{{ Storage::url($submission->formulir_terisi_path) }}" target="_blank" class="text-indigo-600 underline">lihat file</a> (upload ulang untuk mengganti).
                            @else
                                Belum diupload.
                            @endif
                        </p>
                        <x-input-error :messages="$errors->get('formulir_terisi')" class="mt-1" />
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('dashboard') }}" class="text-sm text-slate-500 hover:text-slate-700">Kembali ke dashboard</a>
                        <x-primary-button>Upload</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
