<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Elections\Enums\ElectionStatus;
use App\Domain\Results\Services\ResultService;
use App\Exports\ElectionResultExport;
use App\Http\Controllers\Controller;
use App\Models\Election;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Gate;

class ResultController extends Controller
{
    public function index(\Illuminate\Http\Request $request, ResultService $resultService)
    {
        Gate::authorize('viewAny', Election::class);
        $query = Election::with(['votingEvent', 'organization'])
            ->whereIn('status', [ElectionStatus::Closed, ElectionStatus::Archived]);

        if ($request->filled('voting_event_id')) {
            $query->where('voting_event_id', $request->voting_event_id);
        }
        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        $elections = $query->latest()->paginate(20)->withQueryString();
        $votingEvents = \App\Models\VotingEvent::orderBy('name')->get();
        $organizations = \App\Models\Organization::orderBy('name')->get();

        return view('admin.results.index', compact('elections', 'votingEvents', 'organizations'));
    }

    public function show(Election $election, ResultService $resultService)
    {
        Gate::authorize('viewResult', $election);

        // P2-02: bandingkan enum, bukan string mentah (tahan refactor label).
        if (! in_array($election->status, [ElectionStatus::Closed, ElectionStatus::Archived], true)) {
            return back()->withErrors(['error' => 'Hasil hanya dapat dilihat setelah pemilihan ditutup.']);
        }

        $results = $resultService->tally($election);

        return view('admin.results.show', compact('election', 'results'));
    }

    public function export(Election $election, ResultService $resultService)
    {
        Gate::authorize('exportResult', $election);

        if (! in_array($election->status, [ElectionStatus::Closed, ElectionStatus::Archived], true)) {
            return back()->withErrors(['error' => 'Hasil hanya dapat diekspor setelah pemilihan ditutup.']);
        }

        $results = $resultService->tally($election);

        $filename = "hasil-{$election->name}-" . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($results, $election) {
            $file = fopen('php://output', 'w');
            // P2-04: bungkus fputcsv agar nama kandidat/event tidak jadi formula.
            $safe = fn (array $row) => fputcsv($file, \App\Support\ExcelSanitizer::row($row));

            // Election info
            $safe(['Nama Pemilihan', $election->name]);
            $safe(['Status', $election->status->value]);
            $safe(['Periode', $election->starts_at?->format('d/m/Y H:i') . ' - ' . $election->ends_at?->format('d/m/Y H:i')]);
            $safe(['Total Hak Pilih', $results['total_eligible']]);
            $safe(['Total Sudah Vote', $results['total_voted']]);
            $safe(['Partisipasi', $results['turnout'] . '%']);
            $safe([]);

            // Winner
            if ($results['winner']) {
                $safe(['Pemenang', "No. {$results['winner']['candidate_number']} - {$results['winner']['candidate_name']}"]);
                $safe(['Suara Pemenang', $results['winner']['vote_count']]);
                $safe([]);
            }

            // Results header
            $safe(['Peringkat', 'No. Kandidat', 'Nama Kandidat', 'Jumlah Suara', 'Persentase']);

            foreach ($results['results'] as $index => $row) {
                $safe([
                    $index + 1,
                    $row['candidate_number'],
                    $row['candidate_name'],
                    $row['vote_count'],
                    $row['percentage'] . '%',
                ]);
            }

            $safe([]);
            $safe(['Total Suara', $results['total_votes']]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportExcel(Election $election)
    {
        Gate::authorize('exportResult', $election);

        if (! in_array($election->status, [ElectionStatus::Closed, ElectionStatus::Archived], true)) {
            return back()->withErrors(['error' => 'Hasil hanya dapat diekspor setelah pemilihan ditutup.']);
        }

        $filename = "hasil-{$election->name}-" . now()->format('Y-m-d') . '.xlsx';
        
        return Excel::download(new ElectionResultExport($election), $filename);
    }

    public function exportPdf(Election $election, ResultService $resultService)
    {
        Gate::authorize('exportResult', $election);

        if (! in_array($election->status, [ElectionStatus::Closed, ElectionStatus::Archived], true)) {
            return back()->withErrors(['error' => 'Hasil hanya dapat diekspor setelah pemilihan ditutup.']);
        }

        $results = $resultService->tally($election);
        
        $pdf = PDF::loadView('admin.results.pdf.show', compact('election', 'results'));
        
        $filename = "hasil-{$election->name}-" . now()->format('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }
}
