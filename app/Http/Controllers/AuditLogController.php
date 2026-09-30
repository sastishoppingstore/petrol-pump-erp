<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::with('user')->latest('id');

        if ($request->filled('module')) {
            $query->where('module', $request->input('module'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $logs = $query->paginate(25)->withQueryString();
        $modules = AuditLog::select('module')->distinct()->pluck('module');
        $users = User::select('id', 'name')->orderBy('name')->get();

        return view('audit-logs.index', compact('logs', 'modules', 'users'));
    }

    public function export(Request $request): StreamedResponse
    {
        $query = AuditLog::with('user')->latest('id');

        if ($request->filled('module')) {
            $query->where('module', $request->input('module'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="audit_logs_' . date('Y_m_d_His') . '.csv"',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Date Time', 'User', 'Module', 'Action', 'Reference Type', 'Reference ID', 'IP Address']);

            $query->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $log) {
                    fputcsv($handle, [
                        $log->id,
                        $log->created_at?->format('Y-m-d H:i:s'),
                        $log->user?->name ?? 'System',
                        $log->module,
                        $log->action,
                        $log->reference_type,
                        $log->reference_id,
                        $log->ip_address,
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
