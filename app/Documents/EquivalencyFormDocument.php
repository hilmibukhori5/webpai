<?php

namespace App\Documents;

use App\Models\Student;
use Illuminate\Support\Collection;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

/**
 * Formulir Permohonan Penyetaraan Ujian — dilampirkan di BulkDecisionMail.
 * Satu formulir per mahasiswa, mencakup semua modul yang disetujui dari
 * semua skema. Kolom "Kode Ujian" menyesuaikan skema: PKS Baru -> official_code
 * (CF1, TA2...), Adendum PKS Lama -> kode modul (A10, A20...).
 * Rekening tujuan pembayaran dikonfigurasi di config/letter.php (kunci "form").
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

        $this->addLetterhead($section);
        $this->addTitle($section);
        $this->addStudentFields($section, $student);
        $this->addModuleTable($section, $approvedSubmissions);
        $this->addClosing($section);

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

    private function addLetterhead($section): void
    {
        $logoPath = public_path('images/ub-logo.jpeg');

        $table = $section->addTable(['cellMarginTop' => 0, 'cellMarginBottom' => 0, 'cellMarginLeft' => 0, 'cellMarginRight' => 80]);
        $table->addRow(900);

        // Logo UB (kiri)
        $logoCell = $table->addCell(1600, ['vAlign' => 'center']);
        if (file_exists($logoPath)) {
            $logoCell->addImage($logoPath, ['width' => 72, 'height' => 72], false, ['alignment' => Jc::CENTER]);
        }

        // Teks kementerian + universitas (tengah)
        $midCell = $table->addCell(5000, ['vAlign' => 'center']);
        $midCell->addText(
            'KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI',
            ['size' => 9, 'name' => 'Arial Narrow'],
        );
        $midCell->addText(
            'UNIVERSITAS BRAWIJAYA',
            ['bold' => true, 'size' => 16, 'name' => 'Arial Narrow', 'color' => '1F4E7A'],
        );

        // Fakultas + alamat (kanan)
        $rf = ['size' => 8, 'name' => 'Arial Narrow'];
        $rp = ['alignment' => Jc::END];
        $rightCell = $table->addCell(3000, ['vAlign' => 'center']);
        $rightCell->addText('Fakultas Sains, Teknologi, dan Matematika', array_merge($rf, ['bold' => true]), $rp);
        $rightCell->addText('Jl. Veteran, Malang 65145, Indonesia', $rf, $rp);
        $rightCell->addText('Telp-fax: +62341 554403, 551611', $rf, $rp);
        $rightCell->addText('email: mipa@ub.ac.id', $rf, $rp);

        $section->addTextBreak(1);
        $section->addLine(['weight' => 2, 'width' => 540, 'height' => 0, 'flip' => false]);
        $section->addTextBreak(1);
    }

    private function addTitle($section): void
    {
        $section->addText(
            'PERMOHONAN PENYETARAAN UJIAN',
            ['bold' => true, 'size' => 14, 'underline' => 'single'],
            ['alignment' => Jc::CENTER],
        );
        $section->addTextBreak(1);
    }

    private function addStudentFields($section, Student $student): void
    {
        $section->addText('Saya yang bertanda tangan di bawah ini:');
        $section->addTextBreak(1);

        $this->addLabelValue($section, 'Nama', $student->nama);
        $this->addLabelValue($section, 'NIM', $student->no_induk);
        $this->addLabelValue($section, 'Program Studi', $student->prodi ?? '............................................');
        $this->addLabelValue($section, 'Universitas/PTS', 'Universitas Brawijaya');
        $this->addLabelValue($section, 'No. WA/HP', '............................................');
        $this->addLabelValue($section, 'Email', $student->user?->email ?? '............................................');
        $this->addLabelValue($section, 'Alamat', '............................................');

        $section->addTextBreak(1);
    }

    private function addModuleTable($section, Collection $approvedSubmissions): void
    {
        $section->addText(
            'Mengajukan permohonan penyetaraan ujian modul Persyaratan Aktuaris Indonesia (PAI) '.
            'untuk modul-modul sebagai berikut:',
        );
        $section->addTextBreak(1);

        // Urutkan: Adendum PKS Lama dulu (by code), lalu PKS Baru (by code)
        $sorted = $approvedSubmissions
            ->sortBy(fn ($s) => ($s->scheme === 'baru' ? '1' : '0').$s->paiModule->code)
            ->values();

        $bank = config('letter.form');
        $bankName = $bank['bank_name'] ?? 'Bank Mandiri Cabang Tebet Raya';
        $bankNo = $bank['bank_no'] ?? '124-0000-555-772';
        $bankHolder = $bank['bank_holder'] ?? 'Persatuan Aktuaris Indonesia';

        // Lebar kolom (total ~9500 twips, A4 dengan margin 1200 di kiri-kanan)
        $colNo = 400;
        $colKode = 900;
        $colMatkul = 2800;
        $colKlausul = 1500;
        $colBiaya = 1300;
        $colRekening = 2600;

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMarginTop' => 60,
            'cellMarginBottom' => 60,
            'cellMarginLeft' => 80,
            'cellMarginRight' => 80,
        ]);

        // Header
        $bold = ['bold' => true];
        $center = ['alignment' => Jc::CENTER];
        $table->addRow();
        $table->addCell($colNo)->addText('No.', $bold, $center);
        $table->addCell($colKode)->addText('Kode Ujian', $bold, $center);
        $table->addCell($colMatkul)->addText('Mata Ujian', $bold, $center);
        $table->addCell($colKlausul)->addText('Klausul', $bold, $center);
        $table->addCell($colBiaya)->addText('Biaya (Rp)', $bold, $center);
        $table->addCell($colRekening)->addText('Ditujukan ke Rekening', $bold, $center);

        // Baris data
        foreach ($sorted as $i => $submission) {
            $kode = $submission->scheme === 'baru'
                ? ($submission->paiModule->official_code ?? $submission->paiModule->code)
                : $submission->paiModule->code;

            $klausul = $submission->scheme === 'lama' ? 'Adendum PKS Lama' : 'PKS Baru';
            $small = ['size' => 9];

            $table->addRow();
            $table->addCell($colNo)->addText((string) ($i + 1), [], $center);
            $table->addCell($colKode)->addText($kode, [], $center);
            $table->addCell($colMatkul)->addText($submission->paiModule->name);
            $table->addCell($colKlausul)->addText($klausul, [], $center);
            $table->addCell($colBiaya)->addText(
                number_format($submission->price, 0, ',', '.'),
                [],
                ['alignment' => Jc::END],
            );
            $rekenCell = $table->addCell($colRekening);
            $rekenCell->addText($bankName, $small);
            $rekenCell->addText('No. Rek.: '.$bankNo, $small);
            $rekenCell->addText('An. '.$bankHolder, $small);
        }

        // Baris total
        $total = $sorted->sum('price');
        $spanWidth = $colNo + $colKode + $colMatkul + $colKlausul;
        $table->addRow();
        $table->addCell($spanWidth, ['gridSpan' => 4])->addText('Total', $bold, ['alignment' => Jc::END]);
        $table->addCell($colBiaya)->addText(
            'Rp '.number_format($total, 0, ',', '.'),
            $bold,
            ['alignment' => Jc::END],
        );
        $table->addCell($colRekening)->addText('');

        $section->addTextBreak(2);
        $section->addText(
            'Bersamaan dengan surat ini, saya lampirkan transkrip nilai dan dokumen pendukung lainnya '.
            'sebagai bukti kelulusan mata kuliah yang dipersyaratkan.',
        );
        $section->addTextBreak(1);
        $section->addText(
            'Demikian saya sampaikan permohonan ini, atas perhatian dan kerjasamanya saya ucapkan terima kasih.',
        );
    }

    private function addClosing($section): void
    {
        $section->addTextBreak(2);
        $section->addText('Malang, .................................', [], ['alignment' => Jc::END]);
        $section->addTextBreak(1);
        $section->addText('Pemohon,', [], ['alignment' => Jc::END]);
        $section->addTextBreak(4);
        $section->addText('(............................................)', [], ['alignment' => Jc::END]);
        $section->addText('Nama Jelas dan Tanda Tangan', ['size' => 9, 'color' => '64748B'], ['alignment' => Jc::END]);
    }

    private function addLabelValue($section, string $label, string $value): void
    {
        $table = $section->addTable(['cellMarginTop' => 0, 'cellMarginBottom' => 0]);
        $table->addRow();
        $table->addCell(2000)->addText($label);
        $table->addCell(300)->addText(':');
        $table->addCell(7000)->addText($value);
    }
}
