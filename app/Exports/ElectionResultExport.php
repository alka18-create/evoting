<?php

namespace App\Exports;

use App\Models\Election;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;
use App\Support\ExcelSanitizer;

class ElectionResultExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, WithColumnWidths
{
    protected Election $election;

    public function __construct(Election $election)
    {
        $this->election = $election;
    }

    public function collection()
    {
        // Get results with candidate info
        $results = DB::table('ballots')
            ->join('candidates', 'ballots.candidate_id', '=', 'candidates.id')
            ->where('ballots.election_id', $this->election->id)
            ->select('candidates.id', 'candidates.candidate_number', 'candidates.name', DB::raw('COUNT(*) as vote_count'))
            ->groupBy('candidates.id', 'candidates.candidate_number', 'candidates.name')
            ->orderBy('candidates.candidate_number')
            ->get();

        return $results;
    }

    public function headings(): array
    {
        return [
            'No. Urut',
            'Nama Kandidat',
            'Jumlah Suara',
            'Persentase (%)',
        ];
    }

    public function map($row): array
    {
        $totalVotes = DB::table('ballots')
            ->where('election_id', $this->election->id)
            ->count();

        $percentage = $totalVotes > 0 ? round(($row->vote_count / $totalVotes) * 100, 2) : 0;

        // P2-04: netralkan formula injection dari nama kandidat.
        return ExcelSanitizer::row([
            $row->candidate_number,
            $row->name,
            $row->vote_count,
            $percentage,
        ]);
    }

    public function title(): string
    {
        return 'Hasil Pemilihan';
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
            'A' => 12,
            'B' => 35,
            'C' => 15,
            'D' => 18,
        ];
    }
}
