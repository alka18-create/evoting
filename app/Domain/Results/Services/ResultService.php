<?php

namespace App\Domain\Results\Services;

use App\Domain\Elections\Enums\ElectionStatus;
use App\Models\Ballot;
use App\Models\Election;
use App\Models\VoterEligibility;

class ResultService
{
    /**
     * Hitung hasil voting hanya untuk election CLOSED/ARCHIVED.
     *
     * @return array{total_votes: int, total_eligible: int, total_voted: int, turnout: float, winner: ?array, results: array<int, array{candidate_id: int, candidate_name: string, candidate_number: int, candidate_photo: ?string, running_mate_name: ?string, vote_count: int, percentage: float}>}
     */
    public function tally(Election $election): array
    {
        if ($election->status !== ElectionStatus::Closed && $election->status !== ElectionStatus::Archived) {
            throw new \Exception('Hasil hanya dapat dilihat setelah pemilihan ditutup.');
        }

        // Total ballots
        $totalVotes = Ballot::where('election_id', $election->id)->count();

        // Participation stats
        $totalEligible = VoterEligibility::where('election_id', $election->id)->count();
        $totalVoted = VoterEligibility::where('election_id', $election->id)
            ->where('status', 'VOTED')
            ->count();
        $turnout = $totalEligible > 0 ? round(($totalVoted / $totalEligible) * 100, 2) : 0;

        // Results per candidate with eager loading
        $results = Ballot::where('election_id', $election->id)
            ->selectRaw('candidate_id, count(*) as vote_count')
            ->groupBy('candidate_id')
            ->orderByDesc('vote_count')
            ->get()
            ->map(function ($item) use ($totalVotes) {
                $candidate = $item->candidate;
                return [
                    'candidate_id' => $item->candidate_id,
                    'candidate_name' => $candidate->name,
                    'candidate_number' => $candidate->candidate_number,
                    'candidate_photo' => $candidate->photo_path,
                    'running_mate_name' => $candidate->running_mate_name,
                    'vote_count' => $item->vote_count,
                    'percentage' => $totalVotes > 0 ? round(($item->vote_count / $totalVotes) * 100, 2) : 0,
                ];
            })
            ->toArray();

        // Winner
        $winner = null;
        if (count($results) > 0 && $results[0]['vote_count'] > 0) {
            $winner = $results[0];
        }

        return [
            'total_votes' => $totalVotes,
            'total_eligible' => $totalEligible,
            'total_voted' => $totalVoted,
            'turnout' => $turnout,
            'winner' => $winner,
            'results' => $results,
        ];
    }
}
