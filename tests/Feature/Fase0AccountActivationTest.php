<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Fase 0 — Verifikasi akun pasif tidak dapat login', function () {
    it('menolak login admin dengan akun pasif', function () {
        $user = User::factory()->admin()->create([
            'username' => 'admin.pasif',
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'login' => 'admin.pasif',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    });

    it('mengizinkan login admin setelah akun diaktifkan', function () {
        $user = User::factory()->admin()->create([
            'username' => 'admin.aktif',
            'is_active' => false,
        ]);

        // Login harus gagal
        $this->post('/login', [
            'login' => 'admin.aktif',
            'password' => 'password',
        ])->assertSessionHasErrors('login');

        // Aktifkan akun
        $user->update(['is_active' => true]);

        // Login harus berhasil
        $this->post('/login', [
            'login' => 'admin.aktif',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    });

    it('menolak akses admin setelah akun dinonaktifkan (mid-session)', function () {
        $user = User::factory()->admin()->create([
            'is_active' => true,
        ]);

        $this->actingAs($user);

        // Akses admin dashboard harus berhasil
        $this->get(route('admin.dashboard'))->assertOk();

        // Nonaktifkan akun
        $user->update(['is_active' => false]);

        // Akses admin dashboard harus ditolak (redirect ke login)
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    });

    it('SUPER_ADMIN dapat mengaktifkan akun pasif via UserController', function () {
        $superAdmin = User::factory()->superAdmin()->create([
            'is_active' => true,
        ]);

        $passiveUser = User::factory()->admin()->create([
            'is_active' => false,
        ]);

        $this->actingAs($superAdmin);

        $response = $this->post(route('admin.users.toggle-active', $passiveUser));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $passiveUser->refresh();
        $this->assertTrue($passiveUser->is_active);
    });

    it('SUPER_ADMIN dapat menonaktifkan akun aktif', function () {
        $superAdmin = User::factory()->superAdmin()->create([
            'is_active' => true,
        ]);

        $activeUser = User::factory()->admin()->create([
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin);

        $response = $this->post(route('admin.users.toggle-active', $activeUser));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $activeUser->refresh();
        $this->assertFalse($activeUser->is_active);
    });

    it('admin biasa TIDAK dapat mengakses halaman pengguna', function () {
        $admin = User::factory()->admin()->create([
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $this->get(route('admin.users.index'))->assertForbidden();
    });

    it('SUPER_ADMIN tidak dapat menonaktifkan akun sendiri', function () {
        $superAdmin = User::factory()->superAdmin()->create([
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin);

        $response = $this->post(route('admin.users.toggle-active', $superAdmin));

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');

        $superAdmin->refresh();
        $this->assertTrue($superAdmin->is_active);
    });

    it('mencatat audit log saat aktivasi/deaktivasi', function () {
        $superAdmin = User::factory()->superAdmin()->create([
            'is_active' => true,
        ]);

        $targetUser = User::factory()->admin()->create([
            'is_active' => false,
        ]);

        $this->actingAs($superAdmin);

        // Aktifkan
        $this->post(route('admin.users.toggle-active', $targetUser));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'USER_ACTIVATED',
            'resource_type' => 'User',
            'resource_id' => $targetUser->id,
        ]);

        // Nonaktifkan
        $this->post(route('admin.users.toggle-active', $targetUser->fresh()));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'USER_DEACTIVATED',
            'resource_type' => 'User',
            'resource_id' => $targetUser->id,
        ]);
    });
});
