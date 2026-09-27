<?php

namespace App\Domain\Voting\Services;

use App\Domain\Elections\Enums\ElectionStatus;
use App\Domain\Elections\Enums\VotingEventStatus;
use App\Models\Election;
use App\Models\VotingEvent;

/**
 * P0: gerbang votabilitas terpusat — satu-satunya tempat aturan
 * "kapan boleh vote" didefinisikan, dipakai login + kedua jalur voting.
 *
 * - Event: status OPEN + di dalam window starts_at/ends_at (jika diisi).
 * - Election: status OPEN + effective window (inherit event bila null).
 * - Batch: event OPEN + setiap election milik event itu + votable.
 *
 * Election standalone (tanpa event) hanya dinilai dari status + window
 * election itu sendiri, agar alur legacy tidak rusak.
 */
class VotingGate
{
    public static function assertEventOpen(VotingEvent $event): void
    {
        if ($event->status !== VotingEventStatus::Open) {
            throw new \Exception('Event pemilihan belum dibuka atau sudah ditutup.');
        }

        if ($event->starts_at && now()->lt($event->starts_at)) {
            throw new \Exception('Event pemilihan belum dimulai.');
        }

        if ($event->ends_at && now()->gt($event->ends_at)) {
            throw new \Exception('Event pemilihan sudah berakhir.');
        }
    }

    public static function assertElectionVotable(Election $election): void
    {
        if ($election->status !== ElectionStatus::Open) {
            throw new \Exception('Pemilihan belum dibuka atau sudah ditutup.');
        }

        // Hindari N+1 saat dipanggil dalam loop batch.
        $election->loadMissing('votingEvent');

        $startsAt = $election->effectiveStartsAt();
        $endsAt = $election->effectiveEndsAt();

        if ($startsAt && now()->lt($startsAt)) {
            throw new \Exception("Pemilihan {$election->name} belum dimulai.");
        }

        if ($endsAt && now()->gt($endsAt)) {
            throw new \Exception("Pemilihan {$election->name} sudah berakhir.");
        }
    }

    /**
     * Status votability untuk tampilan pemilih (bukan exception):
     * 'VOTABLE' | 'NOT_OPEN' | 'NOT_STARTED' | 'ENDED'.
     */
    public static function electionVotability(Election $election): string
    {
        if ($election->status !== ElectionStatus::Open) {
            return 'NOT_OPEN';
        }

        $election->loadMissing('votingEvent');

        $startsAt = $election->effectiveStartsAt();
        $endsAt = $election->effectiveEndsAt();

        if ($startsAt && now()->lt($startsAt)) {
            return 'NOT_STARTED';
        }

        if ($endsAt && now()->gt($endsAt)) {
            return 'ENDED';
        }

        return 'VOTABLE';
    }

    /**
     * @param  iterable<int, Election>  $elections
     */
    public static function assertBatchVotable(VotingEvent $event, iterable $elections): void
    {
        self::assertEventOpen($event);

        foreach ($elections as $election) {
            if ((int) $election->voting_event_id !== (int) $event->id) {
                throw new \Exception("Pemilihan {$election->name} tidak termasuk dalam event ini.");
            }

            self::assertElectionVotable($election);
        }
    }
}
