<?php

use App\Models\Election;
use App\Models\User;
use App\Models\VotingEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superadmin = User::factory()->superAdmin()->create(['is_active' => true]);
    $this->actingAs($this->superadmin);
});

it('superadmin dapat menghapus user tanpa relasi', function () {
    $target = User::factory()->create(['is_active' => true]);

    $this->delete(route('admin.users.destroy', $target))
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('success');

    expect(User::find($target->id))->toBeNull();
});

it('menghapus user pembuat data tidak 500 — ditolak dengan pesan jelas', function () {
    $target = User::factory()->create(['is_active' => true]);
    Election::factory()->create(['created_by' => $target->id]);
    VotingEvent::factory()->create(['created_by' => $target->id]);

    $this->delete(route('admin.users.destroy', $target))
        ->assertRedirect()
        ->assertSessionHasErrors('error');

    // Data tetap utuh.
    expect(User::find($target->id))->not->toBeNull();
    expect(Election::where('created_by', $target->id)->count())->toBe(1);
});

it('superadmin tidak dapat menghapus akun sendiri', function () {
    $this->delete(route('admin.users.destroy', $this->superadmin))
        ->assertSessionHasErrors('error');

    expect(User::find($this->superadmin->id))->not->toBeNull();
});
