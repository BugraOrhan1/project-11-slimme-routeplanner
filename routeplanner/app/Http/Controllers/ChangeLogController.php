<?php

namespace App\Http\Controllers;

use App\Models\ChangeLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * US27 – Wijzigingslog: wie heeft wat aangepast.
 */
class ChangeLogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['user', 'type']);

        $logs = ChangeLog::with('user:id,name,role')
            ->when($filters['user'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('subject_type', $type))
            ->latest('created_at')->latest('id')
            ->paginate(30)->withQueryString()
            ->through(fn (ChangeLog $log) => [
                'id' => $log->id,
                'user' => $log->user?->name ?? 'Systeem',
                'role' => $log->user?->roleLabel(),
                'action' => $log->action,
                'type' => $log->subject_type,
                'description' => $log->description,
                'changes' => $log->changes,
                'at' => $log->created_at->format('d-m-Y H:i'),
            ]);

        return Inertia::render('Changes/Index', [
            'logs' => $logs,
            'filters' => $filters,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'types' => ChangeLog::distinct()->orderBy('subject_type')->pluck('subject_type'),
        ]);
    }
}
