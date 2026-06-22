<?php

namespace App\Exports;

use App\Models\PaiModule;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EligibleBelumDiajukanExport implements FromArray, WithHeadings, WithColumnWidths, WithStyles, WithEvents
{
    public function __construct(
        private Collection $rows,
        private PaiModule $module,
    ) {}

    public function headings(): array
    {
        return ['NO', 'NIM', 'NAMA', 'MODUL PAI YANG BISA DIAJUKAN'];
    }

    public function array(): array
    {
        return $this->rows->values()->map(fn ($row, $i) => [
            $i + 1,
            $row['student']->no_induk, // di-override sebagai string di AfterSheet
            $row['student']->nama,
            strtoupper($this->module->code.' '.$this->module->name),
        ])->all();
    }

    public function columnWidths(): array
    {
        return ['A' => 8, 'B' => 22, 'C' => 38, 'D' => 32];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF4F46E5']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $total = $this->rows->count();

                // NIM wajib explicit string — kalau tidak, Excel auto-konversi jadi angka
                foreach ($this->rows->values() as $i => $row) {
                    $excelRow = $i + 2; // +1 header, +1 karena 1-indexed
                    $sheet->getCellByColumnAndRow(2, $excelRow)
                          ->setValueExplicit($row['student']->no_induk, DataType::TYPE_STRING);
                }

                // Freeze header row
                $sheet->freezePane('A2');

                // Auto-filter
                $sheet->setAutoFilter('A1:D'.($total + 1));

                // Row alignment
                if ($total > 0) {
                    $sheet->getStyle('A2:D'.($total + 1))
                          ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    $sheet->getStyle('A2:A'.($total + 1))
                          ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            },
        ];
    }
}
