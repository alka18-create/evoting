<?php

use App\Domain\Elections\Enums\ElectionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('ElectionStatus Enum', function () {

    it('memiliki semua status yang benar', function () {
        expect(ElectionStatus::Draft->value)->toBe('DRAFT');
        expect(ElectionStatus::Scheduled->value)->toBe('SCHEDULED');
        expect(ElectionStatus::Open->value)->toBe('OPEN');
        expect(ElectionStatus::Closed->value)->toBe('CLOSED');
        expect(ElectionStatus::Archived->value)->toBe('ARCHIVED');
    });

    it('label benar untuk setiap status', function () {
        expect(ElectionStatus::Draft->label())->toBe('Draft');
        expect(ElectionStatus::Scheduled->label())->toBe('Terjadwal');
        expect(ElectionStatus::Open->label())->toBe('Berlangsung');
        expect(ElectionStatus::Closed->label())->toBe('Selesai');
        expect(ElectionStatus::Archived->label())->toBe('Diarsipkan');
    });

    it('DRAFT hanya bisa transisi ke SCHEDULED', function () {
        $transitions = ElectionStatus::Draft->allowedTransitions();
        expect($transitions)->toHaveCount(1);
        expect($transitions)->toContain(ElectionStatus::Scheduled);
    });

    it('SCHEDULED hanya bisa transisi ke OPEN', function () {
        $transitions = ElectionStatus::Scheduled->allowedTransitions();
        expect($transitions)->toHaveCount(1);
        expect($transitions)->toContain(ElectionStatus::Open);
    });

    it('OPEN hanya bisa transisi ke CLOSED', function () {
        $transitions = ElectionStatus::Open->allowedTransitions();
        expect($transitions)->toHaveCount(1);
        expect($transitions)->toContain(ElectionStatus::Closed);
    });

    it('CLOSED hanya bisa transisi ke ARCHIVED', function () {
        $transitions = ElectionStatus::Closed->allowedTransitions();
        expect($transitions)->toHaveCount(1);
        expect($transitions)->toContain(ElectionStatus::Archived);
    });

    it('ARCHIVED tidak memiliki transisi', function () {
        $transitions = ElectionStatus::Archived->allowedTransitions();
        expect($transitions)->toHaveCount(0);
    });

    it('canTransitionTo benar untuk transisi valid', function () {
        expect(ElectionStatus::Draft->canTransitionTo(ElectionStatus::Scheduled))->toBeTrue();
        expect(ElectionStatus::Scheduled->canTransitionTo(ElectionStatus::Open))->toBeTrue();
        expect(ElectionStatus::Open->canTransitionTo(ElectionStatus::Closed))->toBeTrue();
        expect(ElectionStatus::Closed->canTransitionTo(ElectionStatus::Archived))->toBeTrue();
    });

    it('canTransitionTo benar untuk transisi tidak valid', function () {
        expect(ElectionStatus::Draft->canTransitionTo(ElectionStatus::Open))->toBeFalse();
        expect(ElectionStatus::Draft->canTransitionTo(ElectionStatus::Closed))->toBeFalse();
        expect(ElectionStatus::Open->canTransitionTo(ElectionStatus::Scheduled))->toBeFalse();
        expect(ElectionStatus::Archived->canTransitionTo(ElectionStatus::Closed))->toBeFalse();
    });

    it('urutan lifecycle benar', function () {
        $status = ElectionStatus::Draft;

        $status = $status->allowedTransitions()[0];
        expect($status)->toBe(ElectionStatus::Scheduled);

        $status = $status->allowedTransitions()[0];
        expect($status)->toBe(ElectionStatus::Open);

        $status = $status->allowedTransitions()[0];
        expect($status)->toBe(ElectionStatus::Closed);

        $status = $status->allowedTransitions()[0];
        expect($status)->toBe(ElectionStatus::Archived);

        expect($status->allowedTransitions())->toHaveCount(0);
    });
});
