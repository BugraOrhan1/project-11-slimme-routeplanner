<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Assignment;
use App\Models\Inspector;
use App\Models\Route;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Maakt routevoorstellen per inspecteur (US13-US15).
 *
 * Werkwijze zoals de planner van Klink het nu doet (gesprek 23-09): stations van
 * verschillende klanten die in dezelfde regio liggen op één dag combineren.
 *
 * 1. Kies per inspecteur een "startopdracht": een urgente opdracht dicht bij zijn startadres.
 * 2. Voeg steeds de opdracht toe die de minste extra rijtijd kost (cheapest insertion),
 *    zolang de werkdag (inclusief terugrijden) niet vol is. Urgente deadlines wegen zwaarder.
 * 3. Verbeter de volgorde met 2-opt.
 *
 * Alleen inspecteurs met de juiste (geldige) certificaten krijgen een opdracht.
 * Het resultaat is een VOORSTEL: de planner keurt het goed of past het aan.
 */
class RoutePlanner
{
    public function __construct(private TravelEstimator $travel) {}

    /**
     * @param  array<int>  $inspectorIds
     * @return Collection<int, Route>
     */
    public function propose(string $date, array $inspectorIds, ?int $userId = null): Collection
    {
        $day = Carbon::parse($date)->startOfDay();

        return DB::transaction(function () use ($day, $inspectorIds, $userId) {
            // Oude, nog niet goedgekeurde voorstellen voor deze dag vervangen
            Route::whereDate('date', $day)->whereIn('inspector_id', $inspectorIds)
                ->where('status', 'voorstel')->get()->each->delete();

            $candidates = $this->candidates($day);
            $inspectors = Inspector::with('certificates')->whereIn('id', $inspectorIds)->where('active', true)->get();
            $routes = collect();

            foreach ($inspectors as $inspector) {
                if (Route::whereDate('date', $day)->where('inspector_id', $inspector->id)->exists()) {
                    continue; // heeft al een goedgekeurde route
                }

                $qualified = $candidates->filter(fn (Assignment $a) => $inspector->isQualifiedFor($a, $day));
                $order = $this->buildOrder($inspector, $qualified, $day);
                if ($order === []) {
                    continue;
                }

                $order = $this->twoOpt($inspector, $order);
                $candidates = $candidates->reject(fn (Assignment $a) => in_array($a->id, array_map(fn ($o) => $o->id, $order), true));

                $route = Route::create([
                    'inspector_id' => $inspector->id,
                    'date' => $day->toDateString(),
                    'status' => 'voorstel',
                    'created_by' => $userId,
                ]);
                foreach ($order as $i => $assignment) {
                    $route->stops()->create(['assignment_id' => $assignment->id, 'position' => $i + 1]);
                }
                $routes->push($this->recalculate($route));
            }

            return $routes;
        });
    }

    /**
     * Open opdrachten die nog niet in een route zitten, meest urgente eerst.
     * Opdrachten uit maandblokken verder dan planning.lookahead_months vooruit worden (nog) niet meegenomen.
     */
    public function candidates(Carbon $day): Collection
    {
        return Assignment::with(['location' => fn ($q) => $q->withCount('tanks'), 'activities'])
            ->where('status', 'open')
            ->whereDoesntHave('stop')
            ->where(fn ($q) => $q->whereNull('plan_month')->orWhere('plan_month', '<=', $day->copy()->addMonthsNoOverflow((int) config('planning.lookahead_months', 1))->endOfMonth()->toDateString()))
            ->orderBy('deadline')->orderBy('priority')
            ->get()
            ->filter(fn (Assignment $a) => $a->location->latitude && $a->location->longitude)
            ->values();
    }

    /** @return array<Assignment> */
    private function buildOrder(Inspector $inspector, Collection $candidates, Carbon $day): array
    {
        if ($candidates->isEmpty()) {
            return [];
        }

        $home = [$inspector->start_latitude, $inspector->start_longitude];
        $dayMinutes = $this->minutesBetween($inspector->work_start, $inspector->work_end);
        $urgentUntil = $day->copy()->addDays((int) config('planning.urgent_days', 21));

        // 1. Startopdracht: van de 8 meest urgente, degene die het dichtst bij huis ligt
        $seed = $candidates->take(8)
            ->filter(fn (Assignment $a) => $this->fits([$a], $home, $dayMinutes))
            ->sortBy(fn (Assignment $a) => $this->travel->minutes($home, $this->point($a)))
            ->first();
        if (! $seed) {
            return [];
        }

        $route = [$seed];
        $remaining = $candidates->reject(fn (Assignment $a) => $a->id === $seed->id)->values()->all();

        // 2. Cheapest insertion
        while (true) {
            $best = null;
            foreach ($remaining as $key => $assignment) {
                foreach (range(0, count($route)) as $pos) {
                    $trial = $route;
                    array_splice($trial, $pos, 0, [$assignment]);
                    $extra = $this->driveMinutes($trial, $home) - $this->driveMinutes($route, $home);
                    // Urgente opdrachten wegen zwaarder: die lijken "dichterbij"
                    $score = $assignment->deadline->lte($urgentUntil) ? $extra * 0.6 : $extra;
                    if ($best === null || $score < $best['score']) {
                        if ($this->fits($trial, $home, $dayMinutes)) {
                            $best = ['score' => $score, 'key' => $key, 'route' => $trial, 'extra' => $extra];
                        }
                    }
                }
            }
            // Niet eindeloos ver rijden voor één extra station
            if ($best === null || $best['extra'] > 90) {
                break;
            }
            $route = $best['route'];
            unset($remaining[$best['key']]);
        }

        return $route;
    }

    /** 3. Volgorde verbeteren door stukken route om te draaien (2-opt). */
    private function twoOpt(Inspector $inspector, array $route): array
    {
        $home = [$inspector->start_latitude, $inspector->start_longitude];
        $improved = true;
        while ($improved && count($route) > 2) {
            $improved = false;
            for ($i = 0; $i < count($route) - 1; $i++) {
                for ($k = $i + 1; $k < count($route); $k++) {
                    $trial = array_merge(
                        array_slice($route, 0, $i),
                        array_reverse(array_slice($route, $i, $k - $i + 1)),
                        array_slice($route, $k + 1),
                    );
                    if ($this->driveMinutes($trial, $home) < $this->driveMinutes($route, $home)) {
                        $route = $trial;
                        $improved = true;
                    }
                }
            }
        }

        return $route;
    }

    /**
     * Berekent afstanden, aankomst- en vertrektijden opnieuw (na voorstel of handmatige wijziging).
     */
    public function recalculate(Route $route): Route
    {
        $route->load(['inspector', 'stops.assignment.activities', 'stops.assignment.location' => fn ($q) => $q->withCount('tanks')]);
        $inspector = $route->inspector;
        $position = [$inspector->start_latitude, $inspector->start_longitude];
        $clock = Carbon::parse($route->date->toDateString().' '.$inspector->work_start);
        $totalKm = 0;
        $totalDrive = 0;
        $totalWork = 0;

        foreach ($route->stops as $i => $stop) {
            $point = $this->point($stop->assignment);
            $km = $this->travel->km($position, $point);
            $drive = $this->travel->minutes($position, $point);
            $clock->addMinutes($drive);
            $arrival = $clock->format('H:i');
            $work = $stop->assignment->estimatedMinutes();
            $clock->addMinutes($work);

            $stop->update([
                'position' => $i + 1,
                'distance_km' => round($km, 1),
                'drive_minutes' => $drive,
                'planned_arrival' => $arrival,
                'planned_departure' => $clock->format('H:i'),
            ]);

            $totalKm += $km;
            $totalDrive += $drive;
            $totalWork += $work;
            $position = $point;
        }

        // Terug naar het startadres
        $home = [$inspector->start_latitude, $inspector->start_longitude];
        $totalKm += $this->travel->km($position, $home);
        $back = $this->travel->minutes($position, $home);
        $totalDrive += $back;
        $clock->addMinutes($back);

        $route->updateQuietly([
            'total_km' => round($totalKm, 1),
            'total_drive_minutes' => $totalDrive,
            'total_work_minutes' => $totalWork,
            'end_time' => $clock->format('H:i'),
        ]);

        return $route->fresh(['inspector', 'stops.assignment.location.customer', 'stops.assignment.activities']);
    }

    /**
     * De planner keurt het voorstel goed: opdrachten worden "ingepland" en
     * meldingen over die deadlines zijn niet meer nodig.
     */
    public function approve(Route $route, ?int $userId): void
    {
        DB::transaction(function () use ($route, $userId) {
            $route->update(['status' => 'goedgekeurd', 'approved_by' => $userId, 'approved_at' => now()]);
            $assignmentIds = $route->stops()->pluck('assignment_id');
            Assignment::whereIn('id', $assignmentIds)->where('status', 'open')->update(['status' => 'ingepland']);
            Alert::whereIn('assignment_id', $assignmentIds)->whereNull('read_at')->update(['read_at' => now()]);
        });
    }

    /**
     * Waarschuwingen voor de planner: werkdag te lang, buiten openingstijden, certificaat ontbreekt.
     *
     * @return array<string>
     */
    public function conflicts(Route $route): array
    {
        $inspector = $route->inspector->loadMissing('certificates');
        $messages = [];

        if ($route->end_time && $route->end_time > $inspector->work_end) {
            $messages[] = "Werkdag loopt uit tot {$this->hm($route->end_time)} (werktijd tot {$this->hm($inspector->work_end)}).";
        }

        foreach ($route->stops as $stop) {
            $location = $stop->assignment->location;
            $name = $location->name;
            if ($location->open_from && $stop->planned_arrival < $location->open_from) {
                $messages[] = "{$name}: aankomst {$this->hm($stop->planned_arrival)} is voor openingstijd {$this->hm($location->open_from)}.";
            }
            if ($location->open_until && $stop->planned_departure > $location->open_until) {
                $messages[] = "{$name}: vertrek {$this->hm($stop->planned_departure)} is na sluitingstijd {$this->hm($location->open_until)}.";
            }
            if (! $inspector->isQualifiedFor($stop->assignment, $route->date)) {
                $messages[] = "{$name}: {$inspector->name} heeft niet alle benodigde (geldige) certificaten.";
            }
            if ($stop->assignment->deadline->lt($route->date)) {
                $messages[] = "{$name}: deadline ({$stop->assignment->deadline->format('d-m-Y')}) ligt voor de routedatum.";
            }
        }

        return $messages;
    }

    private function fits(array $route, array $home, int $dayMinutes): bool
    {
        $work = array_sum(array_map(fn (Assignment $a) => $a->estimatedMinutes(), $route));

        return $work + $this->driveMinutes($route, $home) <= $dayMinutes;
    }

    /** Totale rijtijd: huis -> stops -> huis. */
    private function driveMinutes(array $route, array $home): int
    {
        $total = 0;
        $position = $home;
        foreach ($route as $assignment) {
            $point = $this->point($assignment);
            $total += $this->travel->minutes($position, $point);
            $position = $point;
        }

        return $total + $this->travel->minutes($position, $home);
    }

    private function point(Assignment $assignment): array
    {
        return [$assignment->location->latitude, $assignment->location->longitude];
    }

    private function minutesBetween(string $from, string $until): int
    {
        return (int) Carbon::parse($from)->diffInMinutes(Carbon::parse($until));
    }

    private function hm(?string $time): string
    {
        return substr((string) $time, 0, 5);
    }
}
