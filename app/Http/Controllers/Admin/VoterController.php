<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Auditing\Services\AuditLogger;
use App\Exports\VoterListExport;
use App\Http\Controllers\Controller;
use App\Models\Voter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

class VoterController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Voter::class);

        $query = Voter::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('student_id', 'ilike', "%{$search}%")
                  ->orWhere('class_name', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $voters = $query->latest()->paginate(20)->withQueryString();
        return view('admin.voters.index', compact('voters'));
    }

    public function show(Voter $voter)
    {
        Gate::authorize('view', $voter);

        // Catatan verifikasi: relasi eligibilities.credential tidak ada
        // (tabel credentials di-drop migrasi 000010; token kini di eligibilities).
        $voter->load(['eligibilities.election']);

        return view('admin.voters.show', compact('voter'));
    }

    public function create()
    {
        Gate::authorize('create', Voter::class);

        return view('admin.voters.create');
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Voter::class);

        $request->validate([
            'student_id' => 'required|string|max:255|unique:voters',
            'name' => 'required|string|max:255',
            'class_name' => 'required|string|max:255',
        ]);

        $voter = Voter::create([
            'student_id' => $request->input('student_id'),
            'name' => $request->input('name'),
            'class_name' => $request->input('class_name'),
            'is_active' => true,
        ]);

        AuditLogger::log(
            action: 'VOTER_CREATED',
            resourceType: 'Voter',
            resourceId: $voter->id,
            metadata: ['student_id' => $voter->student_id, 'name' => $voter->name]
        );

        return redirect()->route('admin.voters.index')
            ->with('success', "Pemilih \"{$voter->name}\" berhasil ditambahkan.");
    }

    public function edit(Voter $voter)
    {
        Gate::authorize('update', $voter);

        return view('admin.voters.edit', compact('voter'));
    }

    public function update(Request $request, Voter $voter)
    {
        Gate::authorize('update', $voter);

        $request->validate([
            'student_id' => 'required|string|max:255|unique:voters,student_id,' . $voter->id,
            'name' => 'required|string|max:255',
            'class_name' => 'required|string|max:255',
        ]);

        $voter->update($request->only('student_id', 'name', 'class_name'));

        AuditLogger::log(
            action: 'VOTER_UPDATED',
            resourceType: 'Voter',
            resourceId: $voter->id,
            metadata: ['student_id' => $voter->student_id, 'name' => $voter->name]
        );

        return redirect()->route('admin.voters.index')
            ->with('success', "Pemilih \"{$voter->name}\" berhasil diperbarui.");
    }

    public function destroy(Voter $voter)
    {
        Gate::authorize('delete', $voter);

        if ($voter->eligibilities()->exists()) {
            return back()->withErrors(['error' => 'Tidak dapat menghapus pemilih yang sudah memiliki data eligibilitas.']);
        }

        $voterName = $voter->name;

        $voter->delete();

        AuditLogger::log(
            action: 'VOTER_DELETED',
            resourceType: 'Voter',
            resourceId: null,
            metadata: ['student_id' => $voter->student_id, 'name' => $voterName]
        );

        return redirect()->route('admin.voters.index')
            ->with('success', "Pemilih \"{$voterName}\" berhasil dihapus.");
    }

    public function toggleActive(Voter $voter)
    {
        Gate::authorize('toggleActive', $voter);

        $voter->update(['is_active' => ! $voter->is_active]);

        $action = $voter->is_active ? 'VOTER_ACTIVATED' : 'VOTER_DEACTIVATED';

        AuditLogger::log(
            action: $action,
            resourceType: 'Voter',
            resourceId: $voter->id,
            metadata: ['student_id' => $voter->student_id, 'is_active' => $voter->is_active]
        );

        $status = $voter->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Pemilih \"{$voter->name}\" berhasil {$status}.");
    }

    public function downloadTemplate()
    {
        $filename = 'template_pemilih.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['student_id', 'name', 'class_name']);
            fputcsv($handle, ['12345', 'Budi Santoso', 'XII RPL 1']);
            fputcsv($handle, ['12346', 'Siti Aminah', 'XII RPL 2']);
            fputcsv($handle, ['12347', 'Andi Wijaya', 'XI TKJ 1']);
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        Gate::authorize('import', Voter::class);

        // P2-04: batasi baris agar import raksasa tidak DoS + validasi per-baris.
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getPathname(), 'r');
        $header = fgetcsv($handle);

        // Validate header
        $expected = ['student_id', 'name', 'class_name'];
        if (! is_array($header) || array_map('strtolower', array_map('trim', $header)) !== $expected) {
            fclose($handle);

            return back()->withErrors(['csv_file' => 'Format CSV tidak valid. Header harus: student_id,name,class_name']);
        }

        $imported = 0;
        $skipped = 0;
        $line = 1;
        $maxRows = 5000;

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $line++;

                if ($line > $maxRows + 1) {
                    fclose($handle);
                    DB::rollBack();

                    return back()->withErrors(['csv_file' => "File melebihi batas {$maxRows} baris data. Pecah menjadi beberapa file."]);
                }

                if (count($row) !== 3) {
                    $skipped++;
                    continue;
                }

                [$studentId, $name, $className] = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $row);

                // Validasi panjang + karakter agar DB & export aman.
                if (! is_string($studentId) || $studentId === '' || strlen($studentId) > 50
                    || ! preg_match('/^[A-Za-z0-9\-_\/\. ]+$/', $studentId)
                    || ! is_string($name) || $name === '' || strlen($name) > 255
                    || ! is_string($className) || $className === '' || strlen($className) > 100
                ) {
                    $skipped++;
                    continue;
                }

                // Skip if already exists
                if (Voter::where('student_id', $studentId)->exists()) {
                    $skipped++;
                    continue;
                }

                Voter::create([
                    'student_id' => $studentId,
                    'name' => $name,
                    'class_name' => $className,
                    'is_active' => true,
                ]);

                $imported++;
            }

            fclose($handle);
            DB::commit();

            return redirect()->route('admin.voters.index')
                ->with('success', "Import selesai: {$imported} voter berhasil, {$skipped} dilewati.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['csv_file' => 'Gagal import: ' . $e->getMessage()]);
        }
    }

    public function export()
    {
        Gate::authorize('viewAny', Voter::class);

        $filename = 'daftar-pemilih-' . now()->format('Y-m-d') . '.xlsx';
        
        return Excel::download(new VoterListExport(), $filename);
    }
}
