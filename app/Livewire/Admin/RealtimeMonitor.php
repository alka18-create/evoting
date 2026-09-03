<?php

namespace App\Livewire\Admin;

use App\Models\Election;
use App\Models\Ballot;
use App\Models\Voter;
use App\Models\VoterEligibility;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\On;
use Livewire\Component;

class RealtimeMonitor extends Component
{
    public int $totalVoted = 0;
    public int $totalEligible = 0;
    public float $participationRate = 0;
    public array $recentVotes = [];
    public array $perElection = [];
    public array $perOrganization = [];
    public bool $connected = false;
    public string $lastUpdate = '';
    public ?int $votingEventId = null;

    public function mount(?int $votingEventId = null): void
    {
        $this->votingEventId = $votingEventId;
        $this->refreshData();
        $this->connected = true;
        $this->lastUpdate = now()->format('H:i:s');
    }

    #[On('vote-casted')]
    public function onVoteCasted(): void
    {
        $this->refreshData();
    }

    public function render()
    {
        return view('livewire.admin.realtime-monitor');
    }

    public function refreshData(): void
    {
        $electionQuery = Election::query();
        $eligibilityQuery = VoterEligibility::query();

        if ($this->votingEventId && Schema::hasColumn('elections', 'voting_event_id')) {
            $electionIds = Election::where('voting_event_id', $this->votingEventId)->pluck('id');
            $electionQuery->where('voting_event_id', $this->votingEventId);
            if ($electionIds->isNotEmpty()) {
                $eligibilityQuery->whereIn('election_id', $electionIds);
            } else {
                $eligibilityQuery->whereRaw('1=0');
            }
        }

        $this->totalEligible = (clone $eligibilityQuery)->count();
        $this->totalVoted = (clone $eligibilityQuery)->where('status', 'VOTED')->count();
        $this->participationRate = $this->totalEligible > 0
            ? round(($this->totalVoted / $this->totalEligible) * 100, 2)
            : 0;

        // Per-election stats
        $this->perElection = (clone $electionQuery)->withCount(['eligibilities as total_eligible', 'ballots as total_votes'])
            ->where('status', 'OPEN')
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'org' => $e->organization?->name ?? 'Tanpa Organisasi',
                'total_eligible' => $e->total_eligible,
                'total_votes' => $e->total_votes,
                'rate' => $e->total_eligible > 0 ? round(($e->total_votes / $e->total_eligible) * 100, 1) : 0,
            ])
            ->toArray();

        // Per-organization stats (simple & clear) — guard jika tabel belum migrasi
        $this->perOrganization = [];
        if (Schema::hasTable('organizations') && Schema::hasTable('elections') && Schema::hasTable('voter_eligibilities')) {
            try {
                $this->perOrganization = DB::table('organizations')
                    ->leftJoin('elections', 'elections.organization_id', '=', 'organizations.id')
                    ->leftJoin('voter_eligibilities', 'voter_eligibilities.election_id', '=', 'elections.id')
                    ->when($this->votingEventId, fn ($q) => $q->where('elections.voting_event_id', $this->votingEventId))
                    ->select('organizations.id', 'organizations.name')
                    ->selectRaw('COUNT(DISTINCT elections.id) as election_count')
                    ->selectRaw('COUNT(voter_eligibilities.id) as total_eligible')
                    ->selectRaw("COUNT(CASE WHEN voter_eligibilities.status = 'VOTED' THEN 1 END) as total_voted")
                    ->groupBy('organizations.id', 'organizations.name')
                    ->havingRaw('COUNT(DISTINCT elections.id) > 0')
                    ->orderBy('organizations.name')
                    ->get()
                    ->map(fn ($r) => [
                        'id' => $r->id,
                        'name' => $r->name,
                        'election_count' => (int) $r->election_count,
                        'total_eligible' => (int) $r->total_eligible,
                        'total_voted' => (int) $r->total_voted,
                        'total_pending' => (int) $r->total_eligible - (int) $r->total_voted,
                        'rate' => $r->total_eligible > 0 ? round(($r->total_voted / $r->total_eligible) * 100, 1) : 0,
                    ])
                    ->toArray();

                // Elections without organization (legacy) — hanya jika kolom organization_id ada
                if (Schema::hasColumn('elections', 'organization_id')) {
                    $legacyStats = DB::table('elections')
                        ->leftJoin('voter_eligibilities', 'voter_eligibilities.election_id', '=', 'elections.id')
                        ->whereNull('elections.organization_id')
                        ->when($this->votingEventId, fn ($q) => $q->where('elections.voting_event_id', $this->votingEventId))
                        ->selectRaw('COUNT(DISTINCT elections.id) as election_count')
                        ->selectRaw('COUNT(voter_eligibilities.id) as total_eligible')
                        ->selectRaw("COUNT(CASE WHEN voter_eligibilities.status = 'VOTED' THEN 1 END) as total_voted")
                        ->first();

                    if ($legacyStats && $legacyStats->election_count > 0) {
                        $this->perOrganization[] = [
                            'id' => 0,
                            'name' => 'Tanpa Organisasi',
                            'election_count' => (int) $legacyStats->election_count,
                            'total_eligible' => (int) $legacyStats->total_eligible,
                            'total_voted' => (int) $legacyStats->total_voted,
                            'total_pending' => (int) $legacyStats->total_eligible - (int) $legacyStats->total_voted,
                            'rate' => $legacyStats->total_eligible > 0 ? round(($legacyStats->total_voted / $legacyStats->total_eligible) * 100, 1) : 0,
                        ];
                    }
                }
            } catch (\Throwable $e) {
                $this->perOrganization = [];
            }
        }

        // Recent votes (last 10)
        $this->recentVotes = Ballot::select('ballots.id', 'ballots.created_at', 'candidates.candidate_number', 'candidates.name as candidate_name', 'elections.name as election_name')
            ->join('candidates', 'ballots.candidate_id', '=', 'candidates.id')
            ->join('elections', 'ballots.election_id', '=', 'elections.id')
            ->orderBy('ballots.created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($b) => [
                'id' => $b->id,
                'candidate' => 'No. ' . $b->candidate_number . ' - ' . $b->candidate_name,
                'election' => $b->election_name,
                'time' => $b->created_at->format('H:i:s'),
            ])
            ->toArray();

        $this->lastUpdate = now()->format('H:i:s');
    }
}
