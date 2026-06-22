<?php

namespace App\Documents;

use App\Models\Student;
use Illuminate\Support\Carbon;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;

/**
 * Surat Keterangan penyetaraan modul PAI untuk 1 mahasiswa, mencakup SEMUA
 * modul yang sudah disetujui (status=approved). Format ngikutin template
 * resmi yang dikasih user — Nomor & TTE (tanda tangan elektronik) memang
 * dikosongkan, diisi manual setelah didownload.
 *
 * Kolom "Nilai" tergantung skema modul itu: Adendum PKS Lama -> bobot (grade_point,
 * 0-4), PKS Baru -> NA (0-100). "Rata-rata" per modul pakai rata-rata
 * tertimbang SKS (Sigma(nilai*sks)/Sigma(sks)) -- formula sama dengan
 * eligibility 4b di docs/spec.md, dikonfirmasi user 2026-06-16.
 */
class EquivalencyLetterDocument
{
    private const TABLE_WIDTH = 9000;

    public function build(Student $student): PhpWord
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
        $this->addMeta($section);
        $this->addTitle($section);
        $this->addSignerBlock($section);
        $this->addStudentBlock($section, $student);
        $this->addEquivalencyTable($section, $student);
        $this->addClosing($section);

        return $phpWord;
    }

    private function addLetterhead($section): void
    {
        $head = config('letter.letterhead');

        $table = $section->addTable(['cellMarginTop' => 0, 'cellMarginBottom' => 0]);
        $table->addRow();

        $left = $table->addCell(6000);
        $left->addText($head['ministry'], ['bold' => true, 'size' => 11]);
        $left->addText($head['university'], ['bold' => true, 'size' => 14]);

        $right = $table->addCell(6000);
        $right->addText($head['faculty'], ['bold' => true, 'size' => 12], ['alignment' => Jc::END]);
        $right->addText($head['address'], ['size' => 9], ['alignment' => Jc::END]);
        $right->addText($head['phone'], ['size' => 9], ['alignment' => Jc::END]);
        $right->addText($head['email'].'  '.$head['website'], ['size' => 9], ['alignment' => Jc::END]);

        $section->addTextBreak(1);
        $section->addLine(['weight' => 2, 'width' => 540, 'height' => 0, 'flip' => false]);
        $section->addTextBreak(1);
    }

    private function addMeta($section): void
    {
        $section->addText('Nomor : ', ['size' => 12]);
        $section->addText('Hal : '.config('letter.hal'), ['size' => 12]);
        $section->addText(Carbon::now()->translatedFormat('d F Y'), ['size' => 12], ['alignment' => Jc::END]);
        $section->addTextBreak(1);
    }

    private function addTitle($section): void
    {
        $section->addText('SURAT KETERANGAN', ['bold' => true, 'size' => 14, 'underline' => 'single'], ['alignment' => Jc::CENTER]);
        $section->addTextBreak(1);
    }

    private function addSignerBlock($section): void
    {
        $signer = config('letter.signer');

        $section->addText('Yang bertanda tangan di bawah ini:');
        $this->addLabelValueRow($section, 'Nama', $signer['name']);
        $this->addLabelValueRow($section, 'NIP', $signer['nip']);
        $this->addLabelValueRow($section, 'Jabatan', $signer['jabatan']);
        $this->addLabelValueRow($section, 'Instansi', $signer['instansi']);
        $section->addTextBreak(1);
    }

    private function addStudentBlock($section, Student $student): void
    {
        $section->addText('Menerangkan bahwa:');
        $this->addLabelValueRow($section, 'Nama', $student->nama);
        $this->addLabelValueRow($section, 'NIM', $student->no_induk);
        $section->addTextBreak(1);

        $section->addText(
            'Berikut mendapatkan penyetaraan kurikulum Persyaratan Aktuaris Indonesia (PAI) '.
            'dengan daftar sebagai berikut:'
        );
        $section->addTextBreak(1);
    }

    private function addEquivalencyTable($section, Student $student): void
    {

        $submissions = $student->submissions()
            ->where('status', 'approved')
            ->with(['paiModule', 'submissionCourses.course'])
            ->get()
            ->sortBy(fn ($s) => $s->paiModule->code)
            ->values();

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'width' => self::TABLE_WIDTH * 50,
            'unit' => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
            'alignment' => JcTable::CENTER,
        ]);

        $table->addRow();
        foreach (['No' => 700, 'Modul' => 1200, 'Mata Kuliah' => 4200, 'Nilai' => 1400, 'SKS' => 1000] as $label => $width) {
            $table->addCell($width)->addText($label, ['bold' => true], ['alignment' => Jc::CENTER]);
        }

        $no = 0;

        foreach ($submissions as $submission) {
            $no++;
            $components = $submission->submissionCourses;
            $totalWeighted = 0.0;
            $totalSks = 0;

            foreach ($components as $i => $sc) {
                $nilai = $submission->scheme === 'lama' ? (float) $sc->grade_point : (float) $sc->na;
                $sks = $sc->course->sks;
                $totalWeighted += $nilai * $sks;
                $totalSks += $sks;

                $table->addRow();
                $table->addCell(700)->addText($i === 0 ? (string) $no : '', [], ['alignment' => Jc::CENTER]);
                $table->addCell(1200)->addText($i === 0 ? $submission->paiModule->code : '', [], ['alignment' => Jc::CENTER]);
                $table->addCell(4200)->addText($sc->course->name);
                $table->addCell(1400)->addText($this->formatNilai($nilai), [], ['alignment' => Jc::CENTER]);
                $table->addCell(1000)->addText((string) $sks, [], ['alignment' => Jc::CENTER]);
            }

            $rataRata = $totalSks > 0 ? $totalWeighted / $totalSks : 0.0;

            $table->addRow();
            $table->addCell(700)->addText('');
            $table->addCell(1200)->addText('');
            $table->addCell(4200)->addText('Rata-rata', ['bold' => true]);
            $table->addCell(1400)->addText($this->formatNilai($rataRata), ['bold' => true], ['alignment' => Jc::CENTER]);
            $table->addCell(1000)->addText((string) $totalSks, ['bold' => true], ['alignment' => Jc::CENTER]);
        }

        $section->addTextBreak(1);
    }

    private function addClosing($section): void
    {
        $section->addText('Demikian surat keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.');
        $section->addTextBreak(2);

        $signer = config('letter.signer');

        $section->addText($signer['jabatan'], [], ['alignment' => Jc::END]);
        // Ruang kosong buat TTE (tanda tangan elektronik) - sengaja dikosongkan,
        // diisi manual setelah surat didownload.
        $section->addTextBreak(4);
        $section->addText($signer['name'], ['bold' => true, 'underline' => 'single'], ['alignment' => Jc::END]);
        $section->addText('NIP '.$signer['nip'], [], ['alignment' => Jc::END]);
    }

    private function addLabelValueRow($section, string $label, string $value): void
    {
        $table = $section->addTable(['cellMarginTop' => 0, 'cellMarginBottom' => 0]);
        $table->addRow();
        $table->addCell(1500)->addText($label);
        $table->addCell(300)->addText(':');
        $table->addCell(7000)->addText($value);
    }

    private function formatNilai(float $value): string
    {
        // Bobot/NA bulat (mis. 4, 80) ditampilkan tanpa desimal; selain itu 1-2 desimal.
        if (fmod($value, 1.0) === 0.0) {
            return (string) (int) $value;
        }

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
