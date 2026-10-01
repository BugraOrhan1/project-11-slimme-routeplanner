<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Assignment;
use App\Models\User;

/**
 * Maakt meldingen voor de planner en bedrijfsleider als een deadline bijna verloopt
 * en de opdracht nog niet is ingepland (gesprek 23-09, vraag 16).
 */
class DeadlineAlerts
{
    public function generate(): int
    {
        $days = (int) config('planning.alert_days', 14);
        $recipients = User::whereIn('role', User::ALERT_ROLES)->get();
        $created = 0;

        $assignments = Assignment::with('location.customer')
            ->where('status', 'open')
            ->whereDoesntHave('stop', fn ($q) => $q->whereHas('route', fn ($r) => $r->where('status', 'goedgekeurd')))
            ->whereDate('deadline', '<=', now()->addDays($days))
            ->get();

        foreach ($assignments as $assignment) {
            $when = $assignment->deadline->isPast()
                ? 'is verlopen sinds '.$assignment->deadline->format('d-m-Y')
                : 'verloopt op '.$assignment->deadline->format('d-m-Y');
            $message = "Deadline {$assignment->location->customer->name} – {$assignment->location->name} {$when} en is nog niet ingepland.";

            foreach ($recipients as $user) {
                $alert = Alert::firstOrCreate(
                    ['user_id' => $user->id, 'assignment_id' => $assignment->id],
                    ['message' => $message],
                );
                $created += $alert->wasRecentlyCreated ? 1 : 0;
            }
        }

        return $created;
    }
}
