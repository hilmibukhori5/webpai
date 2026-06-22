<?php

namespace App\Documents;

use App\Models\Student;
use Illuminate\Support\Collection;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

/**
 * Formulir Permohonan Penyetaraan Ujian — dilampirkan di BulkDecisionMail.
 * Satu formulir per mahasiswa, mencakup semua modul yang disetujui.
 * Isi MASIH DUMMY: layout & field dasar saja, belum format resmi ASAI/UB.
 */
class EquivalencyFormDocument
{
    public function build(Student $student, Collection $approvedSubmissions): PhpWord
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

        $this->addTitle($section);
        $this->addDummyNotice($section);
        $this->addStudentFields($section, $student);
        $this->addModuleTable($section, $approvedSubmissions);
        $this->addSignatureBlock($section);

        return $phpWord;
    }

    public function toBinaryString(Student $student, Collection $approvedSubmissions): string
    {
        $phpWord = $this->build($student, $approvedSubmissions);

        $tempPath = tempnam(sys_get_temp_dir(), 'formulir').'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

        $binary = file_get_contents($tempPath);
        unlink($tempPath);

        return $binary;
    }

    private function addTitle($section): void
    {
        $section->addText('FORMULIR PERMOHONAN PENYETARAAN UJIAN', ['bold' => true, 'size' => 14, 'underline' => 'single'], ['alignment' => Jc::CENTER]);
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

    private function addStudentFields($section, Student $student): void
    {
        $fields = [
            'Nama' => $student->nama,
            'No Induk (NIM)' => $student->no_induk,
            'Program Studi' => $student->prodi,
        ];

        foreach ($fields as $label => $value) {
            $this->addLabelValueRow($section, $label, (string) $value);
        }

        $section->addTextBreak(1);
    }

    private function addModuleTable($section, Collection $approvedSubmissions): void
    {
        $section->addText('Modul yang diajukan:', ['bold' => true]);
        $section->addTextBreak(1);

        $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'CCCCCC', 'cellMarginTop' => 80, 'cellMarginBottom' => 80, 'cellMarginLeft' => 80, 'cellMarginRight' => 80]);
        $table->addRow();
        $table->addCell(1000)->addText('Kode', ['bold' => true]);
        $table->addCell(3500)->addText('Nama Modul', ['bold' => true]);
        $table->addCell(1500)->addText('Skema', ['bold' => true]);
        $table->addCell(1500)->addText('Biaya (Rp)', ['bold' => true]);

        foreach ($approvedSubmissions as $submission) {
            $table->addRow();
            $table->addCell(1000)->addText($submission->paiModule->code);
            $table->addCell(3500)->addText($submission->paiModule->name);
            $table->addCell(1500)->addText($submission->scheme === 'baru' ? 'PKS Baru' : 'PKS Lama');
            $table->addCell(1500)->addText(number_format($submission->price, 0, ',', '.'));
        }

        $table->addRow();
        $table->addCell(6000, ['gridSpan' => 3])->addText('Total', ['bold' => true]);
        $table->addCell(1500)->addText(number_format($approvedSubmissions->sum('price'), 0, ',', '.'), ['bold' => true]);

        $section->addTextBreak(1);
        $this->addLabelValueRow($section, 'Tanggal Pengisian', '.................................');
        $section->addTextBreak(1);

        $section->addText('Dengan ini saya mengajukan permohonan penyetaraan ujian untuk modul-modul tersebut di atas sesuai skema yang berlaku.');
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
