<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Auditing\Services\AuditLogger;
use App\Domain\Maintenance\Services\DataWipeService;
use App\Exports\VoterListExport;
use App\Exports\VoterTemplateExport;
use App\Http\Controllers\Controller;
use App\Models\Voter;
use App\Models\VoterEligibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

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

        if ($request->filled('class_name')) {
            $query->where('class_name', $request->input('class_name'));
        }

        $voters = $query->latest()->paginate(20)->withQueryString();

        $classes = Voter::query()
            ->whereNotNull('class_name')
            ->where('class_name', '!=', '')
            ->distinct()
            ->orderBy('class_name')
            ->pluck('class_name');

        return view('admin.voters.index', compact('voters', 'classes'));
    }

    public function deleteClassConfirm(Request $request, DataWipeService $wipe)
    {
        Gate::authorize('deleteAny', Voter::class);

        $className = (string) $request->query('class_name', '');

        if ($className === '') {
            return redirect()->route('admin.voters.index')
                ->withErrors(['error' => 'Pilih kelas terlebih dahulu.']);
        }

        $impact = $wipe->impactForClass($className);

        if ($impact['voters'] === 0) {
            return redirect()->route('admin.voters.index', ['class_name' => $className])
                ->withErrors(['error' => "Tidak ada pemilih di kelas \"{$className}\"."]);
        }

        $warnings = [];
        if ($impact['voted'] > 0) {
            $warnings[] = "{$impact['voted']} pemilih di kelas ini sudah memberikan suara. Suara yang masuk TETAP terhitung karena ballot anonim tanpa identitas pemilih.";
        }

        return view('admin.shared.delete-confirm', [
            'title' => 'Hapus Pemilih per Kelas',
            'itemType' => "seluruh pemilih kelas \"{$className}\"",
            'itemName' => $className,
            'impacts' => [
                'Pemilih' => $impact['voters'],
                'Token / eligibilitas' => $impact['eligibilities'],
                'Data event-voter' => $impact['event_voters'],
            ],
            'warnings' => $warnings,
            'action' => route('admin.voters.destroy-class'),
            'cancelUrl' => route('admin.voters.index', ['class_name' => $className]),
            'hiddenFields' => ['class_name' => $className],
        ]);
    }

    public function destroyClass(Request $request, DataWipeService $wipe)
    {
        Gate::authorize('deleteAny', Voter::class);

        $className = (string) $request->input('class_name', '');

        if ($className === '') {
            return back()->withErrors(['error' => 'Kelas tidak valid.']);
        }

        if ($request->input('confirmation') !== $className) {
            if ($request->has('confirmation')) {
                return back()->withErrors(['error' => 'Konfirmasi tidak cocok. Ketik nama kelas persis seperti ditampilkan.']);
            }

            return redirect()->route('admin.voters.delete-class-confirm', ['class_name' => $className]);
        }

        $counts = $wipe->deleteVotersByClass($className);

        AuditLogger::log(
            action: 'VOTERS_DELETED_BY_CLASS',
            resourceType: 'Voter',
            resourceId: null,
            metadata: ['class_name' => $className, 'wiped' => $counts]
        );

        return redirect()->route('admin.voters.index')
            ->with('success', "Kelas \"{$className}\": {$counts['voters']} pemilih berhasil dihapus.");
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

    public function deleteConfirm(Voter $voter, DataWipeService $wipe)
    {
        Gate::authorize('delete', $voter);

        $impact = $wipe->impactForVoter($voter);

        $warnings = [];
        if (VoterEligibility::where('voter_id', $voter->id)->where('status', 'VOTED')->exists()) {
            $warnings[] = 'Pemilih ini sudah memberikan suara. Suara yang masuk TETAP terhitung karena ballot anonim tanpa identitas pemilih.';
        }

        return view('admin.shared.delete-confirm', [
            'title' => 'Hapus Pemilih',
            'itemType' => 'pemilih',
            'itemName' => $voter->name,
            'impacts' => [
                'Token / eligibilitas' => $impact['eligibilities'],
                'Data event-voter' => $impact['event_voters'],
            ],
            'warnings' => $warnings,
            'action' => route('admin.voters.destroy', $voter),
            'cancelUrl' => route('admin.voters.index'),
        ]);
    }

    public function destroy(Voter $voter, Request $request, DataWipeService $wipe)
    {
        Gate::authorize('delete', $voter);

        $impact = $wipe->impactForVoter($voter);
        $related = $impact['eligibilities'] + $impact['event_voters'];

        if ($related > 0 && $request->input('confirmation') !== $voter->name) {
            if ($request->has('confirmation')) {
                return back()->withErrors(['error' => 'Konfirmasi tidak cocok. Ketik nama persis seperti ditampilkan.']);
            }

            return redirect()->route('admin.voters.delete-confirm', $voter);
        }

        $voterName = $voter->name;
        $studentId = $voter->student_id;

        $counts = $wipe->deleteVoter($voter);

        AuditLogger::log(
            action: 'VOTER_DELETED',
            resourceType: 'Voter',
            resourceId: null,
            metadata: ['student_id' => $studentId, 'name' => $voterName, 'wiped' => $counts]
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
        return Excel::download(new VoterTemplateExport(), 'template_pemilih.xlsx');
    }

    public function import(Request $request)
    {
        Gate::authorize('import', Voter::class);

        // P2-04: batasi baris agar import raksasa tidak DoS + validasi per-baris.
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $file = $request->file('excel_file');

        try {
            $rows = IOFactory::load($file->getPathname())->getActiveSheet()->toArray();
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['excel_file' => 'File tidak dapat dibaca. Gunakan template xlsx yang disediakan.']);
        }

        $header = array_shift($rows);

        // Validate header
        $expected = ['student_id', 'name', 'class_name'];
        $normalizedHeader = array_map(fn ($h) => strtolower(trim((string) $h)), (array) $header);
        if ($normalizedHeader !== $expected) {
            return back()->withErrors(['excel_file' => 'Format file tidak valid. Header harus: student_id,name,class_name']);
        }

        $imported = 0;
        $skipped = 0;
        $line = 1;
        $maxRows = 5000;

        DB::beginTransaction();

        try {
            foreach ($rows as $row) {
                $line++;

                if ($line > $maxRows + 1) {
                    DB::rollBack();

                    return back()->withErrors(['excel_file' => "File melebihi batas {$maxRows} baris data. Pecah menjadi beberapa file."]);
                }

                $row = array_values((array) $row);
                if (count($row) !== 3) {
                    $skipped++;
                    continue;
                }

                [$studentId, $name, $className] = array_map(
                    fn ($v) => self::cellToString($v),
                    $row
                );

                // Validasi panjang + karakter agar DB & export aman.
                if ($studentId === '' || strlen($studentId) > 50
                    || ! preg_match('/^[A-Za-z0-9\-_\/\. ]+$/', $studentId)
                    || $name === '' || strlen($name) > 255
                    || $className === '' || strlen($className) > 100
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

            DB::commit();

            return redirect()->route('admin.voters.index')
                ->with('success', "Import selesai: {$imported} voter berhasil, {$skipped} dilewati.");

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['excel_file' => 'Gagal import: ' . $e->getMessage()]);
        }
    }

    /**
     * Normalisasi sel spreadsheet ke string: angka bulat Excel (12345.0)
     * menjadi "12345" tanpa notasi ilmiah; null menjadi ''.
     */
    private static function cellToString(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            return floor($value) == $value ? sprintf('%.0f', $value) : (string) $value;
        }

        return trim((string) $value);
    }

    public function export()
    {
        Gate::authorize('viewAny', Voter::class);

        $filename = 'daftar-pemilih-' . now()->format('Y-m-d') . '.xlsx';
        
        return Excel::download(new VoterListExport(), $filename);
    }
}
