<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * US08b – Meldingen bij naderende deadlines (planner + bedrijfsleider).
 */
class AlertController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Alerts/Index', [
            'alerts' => $request->user()->alerts()->with('assignment')->latest()->take(100)->get()->map(fn (Alert $a) => [
                'id' => $a->id,
                'message' => $a->message,
                'read' => (bool) $a->read_at,
                'at' => $a->created_at->format('d-m-Y H:i'),
                'assignmentId' => $a->assignment_id,
                'deadline' => $a->assignment?->deadline?->toDateString(),
            ]),
            'alertDays' => (int) config('planning.alert_days'),
        ]);
    }

    public function read(Request $request, Alert $alert): RedirectResponse
    {
        abort_unless($alert->user_id === $request->user()->id, 403);
        $alert->update(['read_at' => now()]);

        return back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->alerts()->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'Alle meldingen zijn gelezen.');
    }
}
