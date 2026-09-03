<?php

namespace App\Livewire\Admin;

use App\Models\Election;
use Livewire\Component;

class ElectionStats extends Component
{
    public int $electionId;
    public int $totalCandidates = 0;
    public int $totalEligible = 0;
    public int $totalVoted = 0;
    public float $turnout = 0;
    public string $status = '';

    public function mount(int $electionId): void
    {
        $this->electionId = $electionId;
        $this->refreshStats();
    }

    public function render()
    {
        return view('livewire.admin.election-stats');
    }

    public function refreshStats(): void
    {
        $election = Election::find($this->electionId);
        if (! $election) {
            return;
        }

        $this->status = $election->status->value;
        $this->totalCandidates = $election->candidates()->count();
        $this->totalEligible = $election->eligibilities()->count();
        $this->totalVoted = $election->eligibilities()->where('status', 'VOTED')->count();
        $this->turnout = $this->totalEligible > 0
            ? round(($this->totalVoted / $this->totalEligible) * 100, 1)
            : 0;
    }
}
