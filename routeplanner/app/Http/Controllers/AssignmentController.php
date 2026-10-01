<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Assignment;
use App\Models\Customer;
use App\Models\Location;
use App\Services\Geocoder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * US01 (toevoegen), US01b (importeren), US07 (bekijken + filters).
 */
class AssignmentController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'customer', 'activity', 'region', 'month', 'sort']);

        $query = Assignment::query()
            ->with(['location' => fn ($q) => $q->withCount('tanks'), 'location.customer', 'activities', 'stop.route.inspector'])
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->whereHas('location',
                fn ($l) => $l->where('name', 'like', "%{$search}%")->orWhere('city', 'like', "%{$search}%")->orWhere('address', 'like', "%{$search}%")))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['customer'] ?? null, fn ($q, $id) => $q->whereHas('location', fn ($l) => $l->where('customer_id', $id)))
            ->when($filters['activity'] ?? null, fn ($q, $id) => $q->whereHas('activities', fn ($a) => $a->where('activities.id', $id)))
            ->when($filters['region'] ?? null, fn ($q, $region) => $q->whereHas('location', fn ($l) => $l->where('region', $region)))
            ->when($filters['month'] ?? null, function ($q, $month) {
                $start = Carbon::parse($month.'-01');
                $q->where(fn ($w) => $w->whereBetween('plan_month', [$start, $start->copy()->endOfMonth()])
                    ->orWhereBetween('deadline', [$start, $start->copy()->endOfMonth()]));
            });

        match ($filters['sort'] ?? 'deadline') {
            'nieuw' => $query->latest(),
            'prioriteit' => $query->orderBy('priority')->orderBy('deadline'),
            default => $query->orderBy('deadline'),
        };

        $assignments = $query->paginate(25)->withQueryString()->through(fn (Assignment $a) => [
            ...$this->assignmentData($a),
            'route' => $a->stop ? [
                'id' => $a->stop->route->id,
                'date' => $a->stop->route->date->format('d-m-Y'),
                'inspector' => $a->stop->route->inspector->name,
                'status' => $a->stop->route->status,
            ] : null,
        ]);

        return Inertia::render('Assignments/Index', [
            'assignments' => $assignments,
            'filters' => $filters,
            'options' => $this->filterOptions(),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Assignments/Form', [
            'assignment' => null,
            'locationId' => $request->integer('location') ?: null,
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $assignment = DB::transaction(function () use ($data) {
            $assignment = Assignment::create($data);
            $assignment->activities()->sync($data['activity_ids']);

            return $assignment;
        });

        return redirect()->route('assignments.index')
            ->with('success', "Opdracht voor {$assignment->location->name} is toegevoegd.");
    }

    public function edit(Assignment $assignment): Response
    {
        $assignment->load('activities');

        return Inertia::render('Assignments/Form', [
            'assignment' => [
                'id' => $assignment->id,
                'location_id' => $assignment->location_id,
                'status' => $assignment->status,
                'deadline' => $assignment->deadline->toDateString(),
                'plan_month' => $assignment->plan_month?->format('Y-m'),
                'priority' => $assignment->priority,
                'notes' => $assignment->notes,
                'activity_ids' => $assignment->activities->pluck('id'),
            ],
            'locationId' => $assignment->location_id,
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, Assignment $assignment): RedirectResponse
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($assignment, $data) {
            $assignment->update($data);
            $assignment->activities()->sync($data['activity_ids']);
        });

        return redirect()->route('assignments.index')->with('success', 'Opdracht is bijgewerkt.');
    }

    public function destroy(Assignment $assignment): RedirectResponse
    {
        $assignment->delete();

        return back()->with('success', 'Opdracht is verwijderd.');
    }

    public function importForm(): Response
    {
        return Inertia::render('Assignments/Import', [
            'activities' => Activity::orderBy('name')->pluck('name'),
        ]);
    }

    /**
     * CSV-import van opdrachten uit de jaarcontracten.
     * Kolommen: klant;station;adres;postcode;plaats;deadline;maand;activiteiten;tanks
     */
    public function import(Request $request, Geocoder $geocoder): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']], [], ['file' => 'bestand']);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $first = fgets($handle);
        $delimiter = substr_count($first, ';') >= substr_count($first, ',') ? ';' : ',';
        $header = array_map(fn ($h) => strtolower(trim($h, " \t\n\r\0\x0B\u{FEFF}\"")), str_getcsv($first, $delimiter));
        $activities = Activity::all()->keyBy(fn ($a) => mb_strtolower($a->name));

        $created = 0;
        $errors = [];
        $line = 1;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;
            if (count(array_filter($row)) === 0) {
                continue;
            }
            $r = array_combine($header, array_pad(array_slice($row, 0, count($header)), count($header), null));

            try {
                if (empty($r['klant']) || empty($r['station']) || empty($r['adres']) || empty($r['plaats']) || empty($r['deadline'])) {
                    throw new \RuntimeException('klant, station, adres, plaats en deadline zijn verplicht');
                }

                $customer = Customer::firstOrCreate(['name' => trim($r['klant'])]);
                $location = Location::where('customer_id', $customer->id)->where('name', trim($r['station']))->first();

                if (! $location) {
                    $geo = $geocoder->lookup(trim($r['adres'].' '.($r['postcode'] ?? '').' '.$r['plaats']));
                    if (! $geo) {
                        throw new \RuntimeException('adres "'.$r['adres'].', '.$r['plaats'].'" niet gevonden');
                    }
                    $location = $customer->locations()->create([
                        'name' => trim($r['station']),
                        'address' => trim($r['adres']),
                        'postal_code' => $geo['postalCode'] ?? ($r['postcode'] ?? null),
                        'city' => trim($r['plaats']),
                        'region' => $geo['province'],
                        'latitude' => $geo['lat'],
                        'longitude' => $geo['lng'],
                    ]);
                    foreach (array_filter(array_map('trim', explode('|', $r['tanks'] ?? ''))) as $product) {
                        $location->tanks()->create(['product' => $product]);
                    }
                }

                $activityIds = collect(explode('|', $r['activiteiten'] ?? ''))
                    ->map(fn ($name) => mb_strtolower(trim($name)))->filter()
                    ->map(fn ($name) => $activities[$name]->id ?? throw new \RuntimeException("activiteit \"{$name}\" bestaat niet"));

                $assignment = Assignment::create([
                    'location_id' => $location->id,
                    'deadline' => Carbon::parse($r['deadline'])->toDateString(),
                    'plan_month' => ! empty($r['maand']) ? Carbon::parse($r['maand'].(strlen($r['maand']) <= 7 ? '-01' : ''))->toDateString() : null,
                    'status' => 'open',
                ]);
                $assignment->activities()->sync($activityIds->all() ?: $activities->pluck('id')->take(3)->all());
                $created++;
            } catch (Throwable $e) {
                $errors[] = "Regel {$line}: ".$e->getMessage();
            }
        }
        fclose($handle);

        return redirect()->route('assignments.import')
            ->with('success', "{$created} opdracht(en) geïmporteerd.")
            ->with('error', $errors ? implode("\n", array_slice($errors, 0, 15)) : null);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'location_id' => ['required', 'exists:locations,id'],
            'status' => ['required', Rule::in(Assignment::STATUSES)],
            'deadline' => ['required', 'date'],
            'plan_month' => ['nullable', 'date_format:Y-m'],
            'priority' => ['required', 'integer', 'between:1,3'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'activity_ids' => ['required', 'array', 'min:1'],
            'activity_ids.*' => ['exists:activities,id'],
        ], [
            'activity_ids.required' => 'Kies minimaal één activiteit.',
        ], [
            'location_id' => 'locatie', 'plan_month' => 'maand', 'priority' => 'prioriteit', 'notes' => 'opmerkingen',
        ]);
        $data['plan_month'] = $data['plan_month'] ? $data['plan_month'].'-01' : null;

        return $data;
    }

    private function formOptions(): array
    {
        return [
            'customers' => Customer::with(['locations' => fn ($q) => $q->withCount('tanks')->orderBy('name')])->orderBy('name')->get()
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'locations' => $c->locations->map(fn ($l) => [
                        'id' => $l->id, 'name' => $l->name, 'city' => $l->city, 'tanks' => $l->tanks_count,
                    ]),
                ]),
            'activities' => Activity::with('certificate')->orderBy('name')->get()->map(fn ($a) => [
                'id' => $a->id, 'name' => $a->name, 'minutes' => $a->duration_minutes, 'perTank' => $a->per_tank,
                'certificate' => $a->certificate?->name,
            ]),
            'statuses' => Assignment::STATUSES,
        ];
    }

    private function filterOptions(): array
    {
        return [
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'activities' => Activity::orderBy('name')->get(['id', 'name']),
            'regions' => Location::whereNotNull('region')->distinct()->orderBy('region')->pluck('region'),
            'statuses' => Assignment::STATUSES,
        ];
    }
}
