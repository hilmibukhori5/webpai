<x-mail::message>
# Modul Ditolak

Halo {{ $submission->student->nama }},

Pengajuan penyetaraan untuk modul **{{ $submission->paiModule->code }} - {{ $submission->paiModule->name }}**
telah **ditolak**.

**Alasan:** {{ $submission->rejection_reason }}

Kamu bisa mengajukan ulang dari dashboard setelah memperbaiki data yang diperlukan.

<x-mail::button :url="route('dashboard')">
Lihat Dashboard
</x-mail::button>

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
