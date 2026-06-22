<?php

namespace App\Exports;

use App\Models\CourseGrade;
use App\Models\Submission;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Laporan penyetaraan modul PAI yang SUDAH DISETUJUI admin, per skema
 * (Adendum PKS Lama -> nilai NH, PKS Baru -> nilai NA). Format kolom: No, Nomor
 * Kandidat (kosong, tidak ada di sistem kita), Nomor Mahasiswa, Nama
 * Kandidat, Modul PAI (one-hot A10-A70), lalu grup berulang Kode/Nilai/
 * Semester per matkul komponen yang dipakai saat disetujui (jumlah grup
 * dinamis, ikut modul dengan komponen terbanyak di data yang diexport --
 * lihat docs/spec.md bagian 2, A50 bisa sampai 4 matkul).
 *
 * 1 baris = 1 submission (1 mahasiswa x 1 modul), BUKAN 1 baris per
 * mahasiswa -- mahasiswa dengan >1 modul approved dapat >1 baris, dengan
 * No/Nomor Mahasiswa/Nama dikosongkan di baris kedua dst.
 */
class EquivalencyReportExport implements WithEvents, WithTitle
{
    private const MODULE_CODES = ['A10', 'A20', 'A30', 'A40', 'A50', 'A60', 'A70'];

    private const FIXED_COLS = 4; // No, Nomor Kandidat, Nomor Mahasiswa, Nama Kandidat

    public function __construct(private string $scheme) {}

    public function title(): string
    {
        return $this->scheme === 'lama' ? 'Adendum PKS Lama' : 'PKS Baru';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $submissions = $this->fetchSubmissions();
                $maxComponents = $this->maxComponents($submissions);

                $this->writeHeader($sheet, $maxComponents);
                $this->writeRows($sheet, $submissions);
                $this->styleSheet($sheet, $maxComponents);
            },
        ];
    }

    /**
     * @return Collection<int, Submission>
     */
    private function fetchSubmissions(): Collection
    {
        return Submission::query()
            ->where('status', 'approved')
            ->where('scheme', $this->scheme)
            ->with(['student', 'paiModule', 'submissionCourses.course'])
            ->get()
            ->sortBy(fn (Submission $s) => $s->student->nama)
            ->values();
    }

    /**
     * @param  Collection<int, Submission>  $submissions
     */
    private function maxComponents(Collection $submissions): int
    {
        $max = $submissions->map(fn (Submission $s) => $s->submissionCourses->count())->max();

        return max((int) $max, 1);
    }

    private function writeHeader(Worksheet $sheet, int $maxComponents): void
    {
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Nomor Kandidat');
        $sheet->setCellValue('C1', 'Nomor Mahasiswa');
        $sheet->setCellValue('D1', 'Nama Kandidat');
        $sheet->mergeCells('A1:A2');
        $sheet->mergeCells('B1:B2');
        $sheet->mergeCells('C1:C2');
        $sheet->mergeCells('D1:D2');

        $moduleStartCol = self::FIXED_COLS + 1;
        $moduleEndCol = $moduleStartCol + count(self::MODULE_CODES) - 1;
        $sheet->setCellValue($this->cell($moduleStartCol, 1), 'Modul PAI');
        $sheet->mergeCells("{$this->col($moduleStartCol)}1:{$this->col($moduleEndCol)}1");

        foreach (self::MODULE_CODES as $i => $code) {
            $sheet->setCellValue($this->cell($moduleStartCol + $i, 2), $code);
        }

        $groupStartCol = $moduleEndCol + 1;
        $groupEndCol = $groupStartCol + ($maxComponents * 3) - 1;
        $sheet->setCellValue($this->cell($groupStartCol, 1), 'Mata Kuliah Disetarakan');
        $sheet->mergeCells("{$this->col($groupStartCol)}1:{$this->col($groupEndCol)}1");

        for ($i = 0; $i < $maxComponents; $i++) {
            $base = $groupStartCol + ($i * 3);
            $sheet->setCellValue($this->cell($base, 2), 'Kode');
            $sheet->setCellValue($this->cell($base + 1, 2), 'Nilai');
            $sheet->setCellValue($this->cell($base + 2, 2), 'Semester/Tahun');
        }
    }

    /**
     * @param  Collection<int, Submission>  $submissions
     */
    private function writeRows(Worksheet $sheet, Collection $submissions): void
    {
        $row = 3;
        $no = 0;
        $lastStudentId = null;
        $groupStartCol = self::FIXED_COLS + count(self::MODULE_CODES) + 1;

        foreach ($submissions as $submission) {
            if ($submission->student_id !== $lastStudentId) {
                $no++;
                $lastStudentId = $submission->student_id;
                $sheet->setCellValue("A{$row}", $no);
                // No Induk dipaksa string -- kalau dibiarkan auto-detect, NIM yang
                // semuanya digit bakal disimpan sebagai angka (resiko notasi
                // ilmiah/leading-zero hilang di Excel asli).
                $sheet->setCellValueExplicit("C{$row}", $submission->student->no_induk, DataType::TYPE_STRING);
                $sheet->setCellValue("D{$row}", $submission->student->nama);
            }

            $moduleIndex = array_search($submission->paiModule->code, self::MODULE_CODES, true);
            $sheet->setCellValue($this->cell(self::FIXED_COLS + 1 + $moduleIndex, $row), 1);

            foreach ($submission->submissionCourses as $i => $sc) {
                $base = $groupStartCol + ($i * 3);
                $semester = CourseGrade::where('course_id', $sc->course_id)
                    ->where('no_induk', $submission->student->no_induk)
                    ->orderByDesc('na')
                    ->value('semester');

                $sheet->setCellValue($this->cell($base, $row), $sc->course->code);
                $sheet->setCellValue(
                    $this->cell($base + 1, $row),
                    $this->scheme === 'lama' ? $sc->nh : (float) $sc->na,
                );
                $sheet->setCellValue($this->cell($base + 2, $row), $semester);
            }

            $row++;
        }
    }

    private function styleSheet(Worksheet $sheet, int $maxComponents): void
    {
        $lastColIndex = self::FIXED_COLS + count(self::MODULE_CODES) + ($maxComponents * 3);
        $lastCol = $this->col($lastColIndex);

        $sheet->getStyle("A1:{$lastCol}2")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastCol}2")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);

        for ($i = 1; $i <= $lastColIndex; $i++) {
            $sheet->getColumnDimension($this->col($i))->setAutoSize(true);
        }
    }

    private function col(int $index): string
    {
        return Coordinate::stringFromColumnIndex($index);
    }

    private function cell(int $colIndex, int $row): string
    {
        return $this->col($colIndex).$row;
    }
}
