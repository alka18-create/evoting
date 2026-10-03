<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Template import pemilih (xlsx): header + 3 baris contoh.
 */
class VoterTemplateExport implements FromCollection, WithHeadings
{
    public function headings(): array
    {
        return ['student_id', 'name', 'class_name'];
    }

    public function collection(): Collection
    {
        return collect([
            ['12345', 'Budi Santoso', 'XII RPL 1'],
            ['12346', 'Siti Aminah', 'XII RPL 2'],
            ['12347', 'Andi Wijaya', 'XI TKJ 1'],
        ]);
    }
}
