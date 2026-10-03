<?php

namespace App\Domain\Maintenance\Services;

use App\Models\Ballot;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\Organization;
use App\Models\Voter;
use App\Models\VoterEligibility;
use App\Models\VotingEvent;
use App\Models\VotingEventVoter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Penghapusan data bertingkat (cascade) dari aplikasi — pengganti
 * hapus-via-terminal. Semua destruktif dibungkus transaksi; file
 * (foto, PDF kartu) ikut dibersihkan.
 *
 * Aturan:
 * - Voter dihapus bersama eligibilitas + event-voters miliknya.
 *   Ballot TIDAK menyimpan voter_id (hash-only anonim) sehingga
 *   suara yang sudah masuk tetap terhitung — disebut eksplisit di
 *   halaman konfirmasi.
 * - Election/Event/Organization menghapus seluruh turunannya
 *   (termasuk suara) — wajib konfirmasi ketik-nama di controller.
 * - Voter tidak pernah ikut terhapus oleh cascade election/event/org
 *   (voter bersifat global lintas pemilihan).
 */
class DataWipeService
{
    // ------------------------------------------------------------------
    // Impact (tanpa menghapus) — untuk halaman konfirmasi.
    // ------------------------------------------------------------------

    public function impactForVoter(Voter $voter): array
    {
        return [
            'eligibilities' => VoterEligibility::where('voter_id', $voter->id)->count(),
            'event_voters' => VotingEventVoter::where('voter_id', $voter->id)->count(),
        ];
    }

    public function impactForElection(Election $election): array
    {
        return [
            'candidates' => Candidate::where('election_id', $election->id)->count(),
            'eligibilities' => VoterEligibility::where('election_id', $election->id)->count(),
            'ballots' => Ballot::where('election_id', $election->id)->count(),
            'token_pdfs' => count($this->pdfFiles('el-' . $election->id)),
        ];
    }

    public function impactForVotingEvent(VotingEvent $event): array
    {
        $electionIds = Election::where('voting_event_id', $event->id)->pluck('id');

        return [
            'elections' => $electionIds->count(),
            'candidates' => $electionIds->isEmpty() ? 0 : Candidate::whereIn('election_id', $electionIds)->count(),
            'eligibilities' => $electionIds->isEmpty() ? 0 : VoterEligibility::whereIn('election_id', $electionIds)->count(),
            'ballots' => $electionIds->isEmpty() ? 0 : Ballot::whereIn('election_id', $electionIds)->count(),
            'event_voters' => VotingEventVoter::where('voting_event_id', $event->id)->count(),
            'token_pdfs' => count($this->pdfFiles((string) $event->id))
                + $electionIds->reduce(fn (int $carry, $id) => $carry + count($this->pdfFiles('el-' . $id)), 0),
        ];
    }

    public function impactForOrganization(Organization $organization): array
    {
        $electionIds = Election::where('organization_id', $organization->id)->pluck('id');

        return [
            'elections' => $electionIds->count(),
            'candidates' => $electionIds->isEmpty() ? 0 : Candidate::whereIn('election_id', $electionIds)->count(),
            'eligibilities' => $electionIds->isEmpty() ? 0 : VoterEligibility::whereIn('election_id', $electionIds)->count(),
            'ballots' => $electionIds->isEmpty() ? 0 : Ballot::whereIn('election_id', $electionIds)->count(),
            'token_pdfs' => $electionIds->reduce(fn (int $carry, $id) => $carry + count($this->pdfFiles('el-' . $id)), 0),
        ];
    }

    // ------------------------------------------------------------------
    // Destruktif.
    // ------------------------------------------------------------------

    /** @return array<string, int> */
    public function deleteVoter(Voter $voter): array
    {
        return DB::transaction(function () use ($voter) {
            $counts = [
                'eligibilities' => VoterEligibility::where('voter_id', $voter->id)->delete(),
                'event_voters' => VotingEventVoter::where('voter_id', $voter->id)->delete(),
            ];

            $voter->delete();
            $counts['voters'] = 1;

            return $counts;
        });
    }

    /** @return array<string, int> */
    public function deleteElection(Election $election): array
    {
        return DB::transaction(function () use ($election) {
            $counts = [
                'ballots' => Ballot::where('election_id', $election->id)->delete(),
                'eligibilities' => VoterEligibility::where('election_id', $election->id)->delete(),
            ];

            $photoPaths = Candidate::where('election_id', $election->id)
                ->get(['photo_path', 'running_mate_photo_path'])
                ->flatMap(fn (Candidate $c) => [$c->photo_path, $c->running_mate_photo_path])
                ->filter()
                ->all();
            $counts['candidates'] = Candidate::where('election_id', $election->id)->delete();
            if ($photoPaths !== []) {
                Storage::disk('public')->delete($photoPaths);
            }

            $counts['token_pdfs'] = $this->deletePdfScope('el-' . $election->id);

            $election->delete();
            $counts['elections'] = 1;

            return $counts;
        });
    }

    /** @return array<string, int> */
    public function deleteVotingEvent(VotingEvent $event): array
    {
        return DB::transaction(function () use ($event) {
            $counts = [
                'elections' => 0, 'candidates' => 0, 'eligibilities' => 0,
                'ballots' => 0, 'token_pdfs' => 0,
            ];

            foreach (Election::where('voting_event_id', $event->id)->get() as $election) {
                foreach ($this->deleteElectionNested($election) as $key => $value) {
                    $counts[$key] += $value;
                }
            }

            $counts['event_voters'] = VotingEventVoter::where('voting_event_id', $event->id)->delete();
            $counts['token_pdfs'] += $this->deletePdfScope((string) $event->id);

            $event->delete();
            $counts['voting_events'] = 1;

            return $counts;
        });
    }

    /** @return array<string, int> */
    public function deleteOrganization(Organization $organization): array
    {
        return DB::transaction(function () use ($organization) {
            $counts = [
                'elections' => 0, 'candidates' => 0, 'eligibilities' => 0,
                'ballots' => 0, 'token_pdfs' => 0,
            ];

            foreach (Election::where('organization_id', $organization->id)->get() as $election) {
                foreach ($this->deleteElectionNested($election) as $key => $value) {
                    $counts[$key] += $value;
                }
            }

            if ($organization->logo_path) {
                Storage::disk('public')->delete($organization->logo_path);
            }

            $organization->delete();
            $counts['organizations'] = 1;

            return $counts;
        });
    }

    /**
     * Hapus satu election TANPA commit transaksi sendiri — dipakai di dalam
     * transaksi induk (event/org) agar atomic.
     *
     * @return array<string, int>
     */
    private function deleteElectionNested(Election $election): array
    {
        $counts = [
            'ballots' => Ballot::where('election_id', $election->id)->delete(),
            'eligibilities' => VoterEligibility::where('election_id', $election->id)->delete(),
        ];

        $photoPaths = Candidate::where('election_id', $election->id)
            ->get(['photo_path', 'running_mate_photo_path'])
            ->flatMap(fn (Candidate $c) => [$c->photo_path, $c->running_mate_photo_path])
            ->filter()
            ->all();
        $counts['candidates'] = Candidate::where('election_id', $election->id)->delete();
        if ($photoPaths !== []) {
            Storage::disk('public')->delete($photoPaths);
        }

        $counts['token_pdfs'] = $this->deletePdfScope('el-' . $election->id);

        $election->delete();
        $counts['elections'] = 1;

        return $counts;
    }

    /** @return list<string> */
    private function pdfFiles(string $scope): array
    {
        if (! Storage::disk('local')->exists('token-cards/' . $scope)) {
            return [];
        }

        return Storage::disk('local')->files('token-cards/' . $scope);
    }

    private function deletePdfScope(string $scope): int
    {
        $files = $this->pdfFiles($scope);
        $count = count($files);

        if ($count > 0) {
            Storage::disk('local')->deleteDirectory('token-cards/' . $scope);
        }

        return $count;
    }
}
