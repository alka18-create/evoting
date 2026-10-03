<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Auditing\Services\AuditLogger;
use App\Domain\Maintenance\Services\DataWipeService;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OrganizationController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Organization::class);

        $organizations = Organization::withCount('elections')->latest()->paginate(20);

        return view('admin.organizations.index', compact('organizations'));
    }

    public function create()
    {
        Gate::authorize('create', Organization::class);

        return view('admin.organizations.create');
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Organization::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:organizations,slug',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']) . '-' . Str::random(5);

        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $request->file('logo')->store('organizations', 'public');
        }
        unset($validated['logo']);

        $org = Organization::create($validated);

        AuditLogger::log(action: 'ORGANIZATION_CREATED', resourceType: 'Organization', resourceId: $org->id, metadata: ['name' => $org->name]);

        return redirect()->route('admin.organizations.index')->with('success', 'Organisasi berhasil dibuat.');
    }

    public function edit(Organization $organization)
    {
        Gate::authorize('update', $organization);

        return view('admin.organizations.edit', compact('organization'));
    }

    public function update(Request $request, Organization $organization)
    {
        Gate::authorize('update', $organization);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:organizations,slug,' . $organization->id,
            'description' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = $organization->slug;
        }

        if ($request->hasFile('logo')) {
            if ($organization->logo_path) {
                Storage::disk('public')->delete($organization->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('organizations', 'public');
        }
        unset($validated['logo']);

        $organization->update($validated);

        AuditLogger::log(action: 'ORGANIZATION_UPDATED', resourceType: 'Organization', resourceId: $organization->id, metadata: ['name' => $organization->name]);

        return redirect()->route('admin.organizations.index')->with('success', 'Organisasi diperbarui.');
    }

    public function deleteConfirm(Organization $organization, DataWipeService $wipe)
    {
        Gate::authorize('delete', $organization);

        $impact = $wipe->impactForOrganization($organization);

        $warnings = [];
        if ($impact['ballots'] > 0) {
            $warnings[] = "{$impact['ballots']} suara yang sudah masuk akan ikut terhapus dan hasil berubah.";
        }

        return view('admin.shared.delete-confirm', [
            'title' => 'Hapus Organisasi',
            'itemType' => 'organisasi',
            'itemName' => $organization->name,
            'impacts' => [
                'Pemilihan' => $impact['elections'],
                'Kandidat' => $impact['candidates'],
                'Token / eligibilitas' => $impact['eligibilities'],
                'Suara (ballot)' => $impact['ballots'],
                'File PDF kartu' => $impact['token_pdfs'],
            ],
            'warnings' => $warnings,
            'action' => route('admin.organizations.destroy', $organization),
            'cancelUrl' => route('admin.organizations.index'),
        ]);
    }

    public function destroy(Organization $organization, Request $request, DataWipeService $wipe)
    {
        Gate::authorize('delete', $organization);

        $impact = $wipe->impactForOrganization($organization);

        if ($impact['elections'] > 0 && $request->input('confirmation') !== $organization->name) {
            if ($request->has('confirmation')) {
                return back()->withErrors(['error' => 'Konfirmasi tidak cocok. Ketik nama persis seperti ditampilkan.']);
            }

            return redirect()->route('admin.organizations.delete-confirm', $organization);
        }

        $organizationName = $organization->name;
        $organizationId = $organization->id;

        $counts = $wipe->deleteOrganization($organization);

        AuditLogger::log(action: 'ORGANIZATION_DELETED', resourceType: 'Organization', resourceId: $organizationId, metadata: ['name' => $organizationName, 'wiped' => $counts]);

        return redirect()->route('admin.organizations.index')->with('success', "Organisasi \"{$organizationName}\" berhasil dihapus.");
    }
}
