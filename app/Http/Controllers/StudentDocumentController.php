<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadStudentDocumentsRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class StudentDocumentController extends Controller
{
    public function edit(): View
    {
        $student = auth()->user()->student;

        abort_if(! $student, 404);
        abort_unless(
            $student->submissions()->where('status', 'approved')->exists(),
            403,
            'Belum ada modul yang disetujui.',
        );

        return view('student.documents', ['student' => $student]);
    }

    public function update(UploadStudentDocumentsRequest $request): RedirectResponse
    {
        $student = auth()->user()->student;

        $updates = [];

        if ($request->hasFile('bukti_pembayaran')) {
            $this->deleteExisting($student->bukti_pembayaran_path);
            $updates['bukti_pembayaran_path'] = $request->file('bukti_pembayaran')
                ->store("students/{$student->id}", 'public');
        }

        if ($request->hasFile('formulir_terisi')) {
            $this->deleteExisting($student->formulir_terisi_path);
            $updates['formulir_terisi_path'] = $request->file('formulir_terisi')
                ->store("students/{$student->id}", 'public');
        }

        $student->update($updates);
        $student->refresh();
        $student->refreshPaymentStatus();

        return back()->with('status', $student->payment_status === 'paid'
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
