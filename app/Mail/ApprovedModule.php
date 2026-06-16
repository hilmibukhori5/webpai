<?php

namespace App\Mail;

use App\Documents\EquivalencyFormDocument;
use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Dikirim saat admin menyetujui submission (docs/spec.md bagian 6 & 8 Fase 6).
 * implements ShouldQueue -> otomatis di-queue (QUEUE_CONNECTION=database).
 *
 * Sejak fitur upload bukti bayar (di luar 8 fase asli, ditambah belakangan):
 * email ini juga melampirkan Formulir Permohonan Penyetaraan Ujian (dummy,
 * lihat App\Documents\EquivalencyFormDocument) + link ke halaman upload.
 */
class ApprovedModule extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Submission $submission) {}

    public function envelope(): Envelope
    {
        $module = $this->submission->paiModule;

        return new Envelope(
            subject: "Modul {$module->code} - {$module->name} telah disetujui",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.approved-module',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $module = $this->submission->paiModule;
        $schemeLabel = $this->submission->scheme === 'baru' ? 'Baru' : 'Lama';
        $filename = "Formulir-Penyetaraan-{$module->code}-PKS{$schemeLabel}.docx";

        return [
            Attachment::fromData(
                fn () => (new EquivalencyFormDocument)->toBinaryString($this->submission),
                $filename,
            )->withMime('application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ];
    }
}
