<x-mail::message>
# Modul Disetujui

Halo {{ $submission->student->nama }},

Pengajuan penyetaraan untuk modul **{{ $submission->paiModule->code }} - {{ $submission->paiModule->name }}**
telah **disetujui**.

- Skema: {{ $submission->scheme === 'baru' ? 'PKS Baru' : 'PKS Lama' }}
- Harga: Rp{{ number_format($submission->price, 0, ',', '.') }}

Terlampir di email ini **Formulir Permohonan Penyetaraan Ujian** — isi & tanda tangani
formulir tersebut, lalu upload kembali bersama bukti pembayaran lewat tombol di bawah.
Status pengajuanmu otomatis berubah jadi **Lunas** begitu kedua file itu sudah diupload.

<x-mail::button :url="route('submissions.documents.edit', $submission)">
Upload Bukti Bayar & Formulir
</x-mail::button>

<x-mail::button :url="route('dashboard')" color="secondary">
Lihat Dashboard
</x-mail::button>

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
