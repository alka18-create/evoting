<?php

namespace App\Livewire\Admin;

use App\Models\Election;
use App\Models\Voter;
use App\Models\VoterEligibility;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class DashboardStats extends Component
{
    public int $totalElections = 0;
    public int $activeElections = 0;
    public int $totalVoters = 0;
    public int $totalVotes = 0;
    public int $totalEligible = 0;
    public int $totalVoted = 0;
    public int $totalNotVoted = 0;
    public float $participationRate = 0;
    
    public array $electionsByStatus = [];
    public array $participationByClass = [];

    public function mount(): void
    {
        $this->refreshStats();
    }

    #[On('vote-casted')]
    public function onVoteCasted(): void
    {
        $this->refreshStats();
    }

    public function render()
    {
        return view('livewire.admin.dashboard-stats');
    }

    public function refreshStats(): void
    {
        $this->totalElections = Election::count();
        $this->activeElections = Election::where('status', 'OPEN')->count();
        $this->totalVoters = Voter::count();
        $this->totalVotes = VoterEligibility::where('status', 'VOTED')->count();
        
        // Calculate participation metrics
        $this->totalEligible = VoterEligibility::count();
        $this->totalVoted = VoterEligibility::where('status', 'VOTED')->count();
        $this->totalNotVoted = $this->totalEligible - $this->totalVoted;
        $this->participationRate = $this->totalEligible > 0 
            ? round(($this->totalVoted / $this->totalEligible) * 100, 2) 
            : 0;
        
        // Elections by status
        $this->electionsByStatus = Election::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn($item) => [$item->status->value => $item->count])
            ->toArray();
        
        // Participation by class (top 10)
        $this->participationByClass = DB::table('voters')
            ->leftJoin('voter_eligibilities', 'voters.id', '=', 'voter_eligibilities.voter_id')
            ->select('voters.class_name')
            ->selectRaw('COUNT(DISTINCT voters.id) as total')
            ->selectRaw('COUNT(CASE WHEN voter_eligibilities.status = ? THEN 1 END) as voted', ['VOTED'])
            ->groupBy('voters.class_name')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'class' => $item->class_name,
                    'total' => $item->total,
                    'voted' => $item->voted,
                    'percentage' => $item->total > 0 ? round(($item->voted / $item->total) * 100, 2) : 0,
                ];
            })
            ->toArray();
    }
}
