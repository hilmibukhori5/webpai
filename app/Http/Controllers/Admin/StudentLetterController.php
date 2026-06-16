<?php

namespace App\Http\Controllers\Admin;

use App\Documents\EquivalencyLetterDocument;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Submission;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentLetterController extends Controller
{
    /**
     * Download surat keterangan (.docx) untuk 1 mahasiswa, mencakup SEMUA
     * modul yang sudah disetujui. Nomor & TTE sengaja dikosongkan, diisi
     * manual setelah didownload (docs/spec.md tidak atur ini -- diminta
     * user langsung).
     */
    public function download(Student $student): BinaryFileResponse
    {
        $this->authorize('viewAny', Submission::class);

        abort_unless(
            $student->submissions()->where('status', 'approved')->exists(),
            404,
            'Mahasiswa ini belum punya modul yang disetujui.',
        );

        $phpWord = (new EquivalencyLetterDocument)->build($student);

        $tempPath = tempnam(sys_get_temp_dir(), 'surat').'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

        $filename = 'Surat-Keterangan-'.Str::slug($student->nama).'-'.$student->no_induk.'.docx';

        return response()
            ->download($tempPath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])
            ->deleteFileAfterSend(true);
    }
}
