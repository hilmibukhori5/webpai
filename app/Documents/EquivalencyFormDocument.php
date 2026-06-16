<?php

namespace App\Documents;

use App\Models\Submission;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

/**
 * Formulir Permohonan Penyetaraan Ujian -- dilampirkan otomatis di email
 * ApprovedModule (di luar 8 fase asli spec, ditambah belakangan atas
 * permintaan user). Isinya MASIH DUMMY: layout & field dasar saja, belum
 * format resmi ASAI/UB -- tinggal ganti isi build() kalau template resmi
 * sudah ada, tanda tangan kosong dipakai keduanya.
 *
 * Formulir PKS Lama dan PKS Baru sengaja dibedakan (judul & catatan beda)
 * karena rincian yang perlu diisi mahasiswa berbeda per skema (lihat
 * docs/spec.md bagian 4).
 */
class EquivalencyFormDocument
{
    public function build(Submission $submission): PhpWord
    {
        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(12);

        $section = $phpWord->addSection([
            'marginTop' => 850,
            'marginBottom' => 850,
            'marginLeft' => 1200,
            'marginRight' => 1200,
        ]);

        $this->addTitle($section, $submission);
        $this->addDummyNotice($section);
        $this->addStudentFields($section, $submission);
        $this->addSignatureBlock($section);

        return $phpWord;
    }

    /**
     * Generate langsung ke string biner (.docx), dipakai sebagai lampiran
     * email (Mail\Attachment::fromData) -- IOFactory cuma bisa nulis ke
     * path/stream, jadi lewat file temporer dulu lalu dihapus lagi.
     */
    public function toBinaryString(Submission $submission): string
    {
        $phpWord = $this->build($submission);

        $tempPath = tempnam(sys_get_temp_dir(), 'formulir').'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

        $binary = file_get_contents($tempPath);
        unlink($tempPath);

        return $binary;
    }

    private function addTitle($section, Submission $submission): void
    {
        $schemeLabel = $submission->scheme === 'baru' ? 'PKS BARU' : 'PKS LAMA';

        $section->addText('FORMULIR PERMOHONAN PENYETARAAN UJIAN', ['bold' => true, 'size' => 14, 'underline' => 'single'], ['alignment' => Jc::CENTER]);
        $section->addText("Skema {$schemeLabel}", ['bold' => true, 'size' => 12], ['alignment' => Jc::CENTER]);
        $section->addTextBreak(1);
    }

    private function addDummyNotice($section): void
    {
        $section->addText(
            '*Dokumen ini masih versi dummy (placeholder), belum format resmi ASAI/UB. '.
            'Lengkapi data di bawah, tanda tangani, lalu upload kembali lewat link di email ini.',
            ['italic' => true, 'size' => 9, 'color' => '64748B'],
        );
        $section->addTextBreak(1);
    }

    private function addStudentFields($section, Submission $submission): void
    {
        $student = $submission->student;
        $module = $submission->paiModule;

        $fields = [
            'Nama' => $student->nama,
            'No Induk (NIM)' => $student->no_induk,
            'Program Studi' => $student->prodi,
            'Modul' => "{$module->code} - {$module->name}",
            'Skema' => $submission->scheme === 'baru' ? 'PKS Baru' : 'PKS Lama',
            'Biaya Penyetaraan' => 'Rp'.number_format($submission->price, 0, ',', '.'),
        ];

        foreach ($fields as $label => $value) {
            $this->addLabelValueRow($section, $label, (string) $value);
        }

        $this->addLabelValueRow($section, 'Tanggal Pengisian', '.................................');
        $section->addTextBreak(1);

        $section->addText('Dengan ini saya mengajukan permohonan penyetaraan ujian untuk modul tersebut di atas sesuai skema yang berlaku.');
        $section->addTextBreak(2);
    }

    private function addSignatureBlock($section): void
    {
        $section->addText('Mahasiswa yang mengajukan,', [], ['alignment' => Jc::END]);
        $section->addTextBreak(4);
        $section->addText('(.................................................)', [], ['alignment' => Jc::END]);
        $section->addText('Tanda tangan & nama jelas', ['size' => 9, 'color' => '64748B'], ['alignment' => Jc::END]);
    }

    private function addLabelValueRow($section, string $label, string $value): void
    {
        $table = $section->addTable(['cellMarginTop' => 0, 'cellMarginBottom' => 0]);
        $table->addRow();
        $table->addCell(2200)->addText($label);
        $table->addCell(300)->addText(':');
        $table->addCell(6500)->addText($value);
    }
}
