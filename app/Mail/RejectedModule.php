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
 * Dikirim saat admin menolak submission, sertakan alasan dari field admin
 * (docs/spec.md bagian 6 & 8 Fase 6).
 */
class RejectedModule extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Submission $submission) {}

    public function envelope(): Envelope
    {
        $module = $this->submission->paiModule;

        return new Envelope(
            subject: "Modul {$module->code} - {$module->name} ditolak",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.rejected-module',
        );
    }
}
