<x-mail::message>
# Keputusan Penyetaraan Modul PAI

Halo {{ $student->nama }},

Berikut keputusan pengajuan penyetaraan modul PAI kamu:

@if ($approved->isNotEmpty())
---

**Disetujui:**

@foreach ($approved as $s)
- **{{ $s->paiModule->code }} — {{ $s->paiModule->name }}** ({{ $s->scheme === 'baru' ? 'PKS Baru' : 'PKS Lama' }}) — Rp{{ number_format($s->price, 0, ',', '.') }}
@endforeach

**Total biaya: Rp{{ number_format($totalPrice, 0, ',', '.') }}**

Terlampir di email ini **Formulir Permohonan Penyetaraan Ujian** — isi & tanda tangani
formulir tersebut, lalu upload kembali bersama bukti pembayaran lewat tombol di bawah.
Status otomatis jadi **Lunas** begitu kedua file itu sudah diupload.

<x-mail::button :url="route('student.documents.edit')">
Upload Bukti Bayar & Formulir
</x-mail::button>
@endif

@if ($rejected->isNotEmpty())
---

**Ditolak:**

@foreach ($rejected as $s)
- **{{ $s->paiModule->code }} — {{ $s->paiModule->name }}**: {{ $s->rejection_reason }}
@endforeach

Kamu bisa mengajukan ulang dari dashboard setelah memperbaiki data yang diperlukan.
@endif

<x-mail::button :url="route('dashboard')" color="secondary">
Lihat Dashboard
</x-mail::button>

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
