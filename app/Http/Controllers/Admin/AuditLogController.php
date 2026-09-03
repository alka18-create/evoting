<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', AuditLog::class);

        $query = AuditLog::with('actor');

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        // Filter by resource type
        if ($request->filled('resource_type')) {
            $query->where('resource_type', $request->input('resource_type'));
        }

        // Filter by actor
        if ($request->filled('actor_id')) {
            $query->where('actor_user_id', $request->input('actor_id'));
        }

        // Filter by date range
        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->input('to') . ' 23:59:59');
        }

        // Search in metadata
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('action', 'ilike', "%{$search}%")
                    ->orWhere('resource_type', 'ilike', "%{$search}%")
                    ->orWhereRaw("metadata::text ilike ?", ["%{$search}%"]);
            });
        }

        $logs = $query->latest('created_at')->paginate(30)->withQueryString();

        // Get unique actions and resource types for filters
        $actions = AuditLog::select('action')->distinct()->orderBy('action')->pluck('action');
        $resourceTypes = AuditLog::select('resource_type')->distinct()->whereNotNull('resource_type')->orderBy('resource_type')->pluck('resource_type');

        return view('admin.audit-logs.index', compact('logs', 'actions', 'resourceTypes'));
    }

    public function show(AuditLog $auditLog)
    {
        Gate::authorize('view', $auditLog);

        $auditLog->load('actor');

        return view('admin.audit-logs.show', ['log' => $auditLog]);
    }

    public function export(Request $request)
    {
        Gate::authorize('export', AuditLog::class);

        $query = AuditLog::with('actor');

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }
        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->input('to') . ' 23:59:59');
        }

        $logs = $query->latest('created_at')->get();

        $filename = "audit-logs-" . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['Waktu', 'Aksi', 'Oleh', 'Resource', 'ID', 'IP Address', 'Metadata']);

            foreach ($logs as $log) {
                // P2-04: metadata bisa berisi input admin — netralkan formula.
                fputcsv($file, \App\Support\ExcelSanitizer::row([
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->action,
                    $log->actor?->name ?? 'System/Voter',
                    $log->resource_type ?? '-',
                    $log->resource_id ?? '-',
                    $log->ip_address ?? '-',
                    $log->metadata ? json_encode($log->metadata) : '-',
                ]));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
