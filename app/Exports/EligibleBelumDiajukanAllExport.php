<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export semua modul — 1 baris per modul eligible per mahasiswa.
 * Kolom NO/NIM/NAMA di-merge secara vertikal kalau mahasiswa eligible
 * di lebih dari 1 modul, supaya mudah dibaca.
 *
 * @param Collection<int, array{student: Student, modules: PaiModule[]}> $byStudent
 */
class EligibleBelumDiajukanAllExport implements WithEvents, WithTitle
{
    public function __construct(private Collection $byStudent) {}

    public function title(): string
    {
        return 'Eligible Belum Diajukan';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $this->writeHeader($sheet);
                $lastRow = $this->writeRows($sheet);
                $this->styleSheet($sheet, $lastRow);
            },
        ];
    }

    private function writeHeader(Worksheet $sheet): void
    {
        $sheet->setCellValue('A1', 'NO');
        $sheet->setCellValue('B1', 'NIM');
        $sheet->setCellValue('C1', 'NAMA');
        $sheet->setCellValue('D1', 'MODUL PAI YANG BISA DIAJUKAN');

        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color'    => ['argb' => 'FF4F46E5'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(22);
    }

    private function writeRows(Worksheet $sheet): int
    {
        $row = 2;
        $no  = 1;

        foreach ($this->byStudent as $entry) {
            $student    = $entry['student'];
            $modules    = $entry['modules'];
            $startRow   = $row;
            $moduleCount = count($modules);

            foreach ($modules as $module) {
                $sheet->setCellValue("D{$row}", strtoupper($module->code.' '.$module->name));
                $row++;
            }

            $endRow = $row - 1;

            // NO, NIM, NAMA on the first row of this student's block
            $sheet->setCellValue("A{$startRow}", $no);
            // NIM wajib explicit string supaya tidak auto-konversi ke angka di Excel
            $sheet->setCellValueExplicit("B{$startRow}", $student->no_induk, DataType::TYPE_STRING);
            $sheet->setCellValue("C{$startRow}", $student->nama);

            if ($moduleCount > 1) {
                // Merge NO / NIM / NAMA vertically for multi-module students
                $sheet->mergeCells("A{$startRow}:A{$endRow}");
                $sheet->mergeCells("B{$startRow}:B{$endRow}");
                $sheet->mergeCells("C{$startRow}:C{$endRow}");

                $sheet->getStyle("A{$startRow}:C{$endRow}")
                      ->getAlignment()
                      ->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getStyle("A{$startRow}")
                      ->getAlignment()
                      ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            // Zebra stripe per student block
            if ($no % 2 === 0) {
                $sheet->getStyle("A{$startRow}:D{$endRow}")
                      ->getFill()
                      ->setFillType(Fill::FILL_SOLID)
                      ->getStartColor()
                      ->setARGB('FFF8FAFC');
            }

            $no++;
        }

        return max($row - 1, 1); // lastRow written
    }

    private function styleSheet(Worksheet $sheet, int $lastRow): void
    {
        // Column widths
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(38);
        $sheet->getColumnDimension('D')->setWidth(34);

        $sheet->freezePane('A2');

        if ($lastRow >= 2) {
            $sheet->setAutoFilter("A1:D{$lastRow}");

            // Thin border on all data cells
            $sheet->getStyle("A1:D{$lastRow}")
                  ->getBorders()
                  ->getAllBorders()
                  ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
                  ->getColor()
                  ->setARGB('FFE2E8F0');
        }

        // Module column left-align
        if ($lastRow >= 2) {
            $sheet->getStyle("D2:D{$lastRow}")
                  ->getAlignment()
                  ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                  ->setVertical(Alignment::VERTICAL_CENTER);
        }
    }
}
