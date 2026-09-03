<?php

namespace App\Domain\Elections\Enums;

enum VotingEventStatus: string
{
    case Draft = 'DRAFT';
    case Scheduled = 'SCHEDULED';
    case Open = 'OPEN';
    case Closed = 'CLOSED';
    case Archived = 'ARCHIVED';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Scheduled => 'Terjadwal',
            self::Open => 'Berlangsung',
            self::Closed => 'Selesai',
            self::Archived => 'Diarsipkan',
        };
    }

    /**
     * Transisi valid dari status saat ini.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Scheduled],
            self::Scheduled => [self::Open],
            self::Open => [self::Closed],
            self::Closed => [self::Archived],
            self::Archived => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
