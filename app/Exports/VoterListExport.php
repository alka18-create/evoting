<?php

namespace App\Exports;

use App\Models\Voter;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Support\ExcelSanitizer;

class VoterListExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, WithColumnWidths
{
    public function collection()
    {
        return Voter::orderBy('class_name')->orderBy('name')->get();
    }

    public function headings(): array
    {
        return [
            'NIS',
            'Nama',
            'Kelas',
            'Status',
        ];
    }

    public function map($voter): array
    {
        // P2-04: data import CSV bisa mengandung formula — netralkan saat export.
        return ExcelSanitizer::row([
            $voter->student_id,
            $voter->name,
            $voter->class_name,
            $voter->is_active ? 'Aktif' : 'Nonaktif',
        ]);
    }

    public function title(): string
    {
        return 'Daftar Pemilih';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 35,
            'C' => 15,
            'D' => 12,
        ];
    }
}
