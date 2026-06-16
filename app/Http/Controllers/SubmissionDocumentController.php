<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadSubmissionDocumentsRequest;
use App\Models\Submission;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Upload bukti bayar & formulir permohonan penyetaraan ujian terisi, untuk
 * submission yang sudah disetujui admin (di luar 8 fase asli spec, ditambah
 * belakangan). Begitu KEDUA file ada, payment_status otomatis "paid" --
 * tanpa langkah verifikasi admin tambahan (dikonfirmasi user).
 */
class SubmissionDocumentController extends Controller
{
    public function edit(Submission $submission): View
    {
        $this->authorize('manageDocuments', $submission);

        return view('student.submissions.documents', ['submission' => $submission]);
    }

    public function update(UploadSubmissionDocumentsRequest $request, Submission $submission): RedirectResponse
    {
        $updates = [];

        if ($request->hasFile('bukti_pembayaran')) {
            $this->deleteExisting($submission->bukti_pembayaran_path);
            $updates['bukti_pembayaran_path'] = $request->file('bukti_pembayaran')
                ->store("submissions/{$submission->id}", 'public');
        }

        if ($request->hasFile('formulir_terisi')) {
            $this->deleteExisting($submission->formulir_terisi_path);
            $updates['formulir_terisi_path'] = $request->file('formulir_terisi')
                ->store("submissions/{$submission->id}", 'public');
        }

        $submission->update($updates);
        $submission->refreshPaymentStatus();

        return back()->with('status', $submission->payment_status === 'paid'
            ? 'Kedua dokumen sudah lengkap, status pembayaran kamu otomatis jadi Lunas.'
            : 'File berhasil diupload. Upload juga file yang belum dilengkapi supaya statusnya jadi Lunas.');
    }

    private function deleteExisting(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
