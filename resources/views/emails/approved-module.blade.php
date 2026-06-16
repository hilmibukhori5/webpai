<x-mail::message>
# Modul Disetujui

Halo {{ $submission->student->nama }},

Pengajuan penyetaraan untuk modul **{{ $submission->paiModule->code }} - {{ $submission->paiModule->name }}**
telah **disetujui**.

- Skema: {{ $submission->scheme === 'baru' ? 'PKS Baru' : 'PKS Lama' }}
- Harga: Rp{{ number_format($submission->price, 0, ',', '.') }}

<x-mail::button :url="route('dashboard')">
Lihat Dashboard
</x-mail::button>

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
