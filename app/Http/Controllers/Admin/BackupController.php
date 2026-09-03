<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Auditing\Services\AuditLogger;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;

class BackupController extends Controller
{
    private string $backupPath;

    private const FILENAME_PATTERN = '/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/';

    public function __construct()
    {
        // P0-03: simpan di private disk agar tidak bisa diakses via public URL.
        $this->backupPath = storage_path('app/private/backups');
        if (! File::isDirectory($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0700, true);
        }
    }

    /**
     * P0-02: sanitasi nama file + pastikan tetap di dalam backupPath.
     */
    private function resolvePath(string $filename): ?string
    {
        $base = basename($filename);

        if (! preg_match(self::FILENAME_PATTERN, $base)) {
            return null;
        }

        $full = $this->backupPath . '/' . $base;
        $realBase = realpath($this->backupPath);

        if ($realBase === false) {
            return null;
        }

        // File belum tentu ada (untuk create), jadi validasi prefix path saja.
        // Untuk download/destroy, cek realpath file juga.
        if (File::exists($full)) {
            $realFile = realpath($full);
            if ($realFile === false || ! str_starts_with($realFile, $realBase . DIRECTORY_SEPARATOR)) {
                return null;
            }

            return $realFile;
        }

        // File belum ada: pastikan tidak ada traversal (basename sudah aman).
        if (str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            // basename sudah strip, tapi tolak pola mencurigakan dari input mentah.
            if ($filename !== $base) {
                return null;
            }
        }

        return $full;
    }

    public function index()
    {
        Gate::authorize('manageBackups');

        $backups = [];

        if (File::isDirectory($this->backupPath)) {
            $files = File::files($this->backupPath);

            foreach ($files as $file) {
                $name = $file->getFilename();
                if (! preg_match(self::FILENAME_PATTERN, $name)) {
                    continue;
                }
                $backups[] = [
                    'name' => $name,
                    'path' => $file->getPathname(),
                    'size' => $file->getSize(),
                    'date' => $file->getMTime(),
                ];
            }

            usort($backups, fn ($a, $b) => $b['date'] <=> $a['date']);
        }

        return view('admin.backups.index', compact('backups'));
    }

    public function create()
    {
        Gate::authorize('manageBackups');

        try {
            $filename = 'backup_' . now()->format('Y-m-d_H-i-s') . '.sql';
            $filepath = $this->resolvePath($filename);

            if ($filepath === null) {
                return back()->withErrors(['error' => 'Nama file backup tidak valid.']);
            }

            $connection = config('database.default');
            $pdo = DB::connection($connection)->getPdo();

            $quote = function ($value) use ($pdo): string {
                if (is_null($value)) {
                    return 'NULL';
                }
                if (is_bool($value)) {
                    return $value ? 'TRUE' : 'FALSE';
                }
                if (is_int($value) || is_float($value)) {
                    return (string) $value;
                }
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                // PDO::quote menangani escaping Postgres dengan benar (bukan addslashes).
                $quoted = $pdo->quote((string) $value);
                if ($quoted === false) {
                    throw new \RuntimeException('Gagal meng-quote nilai backup.');
                }

                return $quoted;
            };

            $sql = "-- E-Voting Database Backup (PostgreSQL)\n";
            $sql .= '-- Date: ' . now()->format('Y-m-d H:i:s') . "\n";
            $sql .= '-- Database: ' . config('database.connections.' . $connection . '.database') . "\n";
            $sql .= "-- Restore: psql \"\$DATABASE_URL\" -f {$filename}\n\n";
            $sql .= "BEGIN;\n";
            $sql .= "SET session_replication_role = 'replica';\n\n";

            $tables = DB::connection($connection)
                ->select("SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename");

            foreach ($tables as $table) {
                $tableName = $table->tablename;

                $columns = DB::connection($connection)
                    ->select("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = ? AND table_schema = 'public' ORDER BY ordinal_position", [$tableName]);

                if (empty($columns)) {
                    continue;
                }

                $count = DB::connection($connection)->table($tableName)->count();
                if ($count === 0) {
                    $sql .= "-- Table: {$tableName} (empty)\n\n";
                    continue;
                }

                $sql .= "-- Table: {$tableName} ({$count} rows)\n";
                $sql .= "TRUNCATE TABLE \"{$tableName}\" RESTART IDENTITY CASCADE;\n";

                $columnNames = array_map(fn ($c) => '"' . str_replace('"', '""', $c->column_name) . '"', $columns);

                DB::connection($connection)->table($tableName)->orderBy('id')->chunk(500, function ($rows) use (&$sql, $tableName, $columns, $columnNames, $quote) {
                    foreach ($rows as $row) {
                        $values = [];
                        foreach ($columns as $col) {
                            $values[] = $quote($row->{$col->column_name} ?? null);
                        }
                        $sql .= "INSERT INTO \"{$tableName}\" (" . implode(', ', $columnNames) . ') VALUES (' . implode(', ', $values) . ");\n";
                    }
                });

                $sql .= "\n";
            }

            $sql .= "SET session_replication_role = 'origin';\n";
            $sql .= "COMMIT;\n";

            File::put($filepath, $sql);
            @chmod($filepath, 0600);

            AuditLogger::log(action: 'BACKUP_CREATED', resourceType: 'Backup', resourceId: 0, metadata: ['filename' => $filename]);

            $sizeKB = round(File::size($filepath) / 1024, 2);

            return back()->with('success', "Backup berhasil dibuat: {$filename} ({$sizeKB} KB)");
        } catch (\Exception $e) {
            report($e);

            return back()->withErrors(['error' => 'Gagal membuat backup. Silakan coba lagi.']);
        }
    }

    public function download($filename)
    {
        Gate::authorize('manageBackups');

        $filepath = $this->resolvePath($filename);

        if ($filepath === null || ! File::exists($filepath)) {
            return back()->withErrors(['error' => 'File backup tidak ditemukan.']);
        }

        AuditLogger::log(action: 'BACKUP_DOWNLOADED', resourceType: 'Backup', resourceId: 0, metadata: ['filename' => basename($filepath)]);

        return response()->download($filepath);
    }

    public function destroy($filename)
    {
        Gate::authorize('manageBackups');

        $filepath = $this->resolvePath($filename);

        if ($filepath === null || ! File::exists($filepath)) {
            return back()->withErrors(['error' => 'File backup tidak ditemukan.']);
        }

        File::delete($filepath);

        AuditLogger::log(action: 'BACKUP_DELETED', resourceType: 'Backup', resourceId: 0, metadata: ['filename' => basename((string) $filename)]);

        return back()->with('success', 'Backup berhasil dihapus.');
    }
}
