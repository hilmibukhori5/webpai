<?php

namespace App\Mail;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Dikirim saat admin menyetujui submission (docs/spec.md bagian 6 & 8 Fase 6).
 * implements ShouldQueue -> otomatis di-queue (QUEUE_CONNECTION=database).
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
}
