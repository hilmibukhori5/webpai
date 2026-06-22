<?php

namespace App\Mail;

use App\Documents\EquivalencyFormDocument;
use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Dikirim admin lewat tombol "Kirim Keputusan" di halaman detail mahasiswa.
 * Satu email merangkum semua keputusan modul (approved & rejected) sekaligus,
 * melampirkan Formulir Permohonan Penyetaraan Ujian untuk modul yang disetujui.
 */
class BulkDecisionMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Student $student) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Keputusan Penyetaraan Modul PAI',
        );
    }

    public function content(): Content
    {
        $approved = $this->student->submissions()
            ->with('paiModule')
            ->where('status', 'approved')
            ->orderBy('created_at')
            ->get();

        $rejected = $this->student->submissions()
            ->with('paiModule')
            ->where('status', 'rejected')
            ->orderBy('created_at')
            ->get();

        return new Content(
            markdown: 'emails.bulk-decision',
            with: [
                'student' => $this->student,
                'approved' => $approved,
                'rejected' => $rejected,
                'totalPrice' => $approved->sum('price'),
            ],
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        $approved = $this->student->submissions()
            ->with('paiModule')
            ->where('status', 'approved')
            ->get();

        if ($approved->isEmpty()) {
            return [];
        }

        return [
            Attachment::fromData(
                fn () => (new EquivalencyFormDocument)->toBinaryString($this->student, $approved),
                'Formulir-Penyetaraan-Ujian.docx',
            )->withMime('application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ];
    }
}
