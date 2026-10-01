<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Inspector;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * US04 – Inspecteurs beheren, US05 – certificaten per inspecteur.
 */
class InspectorController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Inspectors/Index', [
            'inspectors' => Inspector::with(['certificates', 'user:id,email'])->orderBy('name')->get()
                ->map(fn (Inspector $i) => [
                    'id' => $i->id,
                    'name' => $i->name,
                    'phone' => $i->phone,
                    'email' => $i->user?->email,
                    'startAddress' => $i->start_address,
                    'workStart' => substr($i->work_start, 0, 5),
                    'workEnd' => substr($i->work_end, 0, 5),
                    'color' => $i->color,
                    'active' => $i->active,
                    'certificates' => $i->certificates->map(fn ($c) => [
                        'name' => $c->name,
                        'validUntil' => $c->pivot->valid_until,
                        'expired' => $c->pivot->valid_until && $c->pivot->valid_until < now()->toDateString(),
                        'expiresSoon' => $c->pivot->valid_until && $c->pivot->valid_until >= now()->toDateString()
                            && $c->pivot->valid_until <= now()->addDays(60)->toDateString(),
                    ]),
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Inspectors/Form', ['inspector' => null, ...$this->options()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($data) {
            $inspector = Inspector::create($data);
            $inspector->certificates()->sync($this->pivot($data['certificates']));
        });

        return redirect()->route('inspectors.index')->with('success', "Inspecteur {$data['name']} is toegevoegd.");
    }

    public function edit(Inspector $inspector): Response
    {
        $inspector->load('certificates');

        return Inertia::render('Inspectors/Form', [
            'inspector' => [
                ...$inspector->only('id', 'name', 'phone', 'user_id', 'start_address', 'start_latitude', 'start_longitude', 'color', 'active'),
                'work_start' => substr($inspector->work_start, 0, 5),
                'work_end' => substr($inspector->work_end, 0, 5),
                'certificates' => $inspector->certificates->map(fn ($c) => ['id' => $c->id, 'valid_until' => $c->pivot->valid_until]),
            ],
            ...$this->options(),
        ]);
    }

    public function update(Request $request, Inspector $inspector): RedirectResponse
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($inspector, $data) {
            $inspector->update($data);
            $inspector->certificates()->sync($this->pivot($data['certificates']));
        });

        return redirect()->route('inspectors.index')->with('success', 'Inspecteur is opgeslagen.');
    }

    public function destroy(Inspector $inspector): RedirectResponse
    {
        $inspector->delete();

        return redirect()->route('inspectors.index')->with('success', "Inspecteur {$inspector->name} is verwijderd.");
    }

    private function options(): array
    {
        return [
            'certificates' => Certificate::orderBy('name')->get(['id', 'name']),
            'users' => User::where('role', 'inspecteur')->orderBy('name')->get(['id', 'name', 'email']),
            'office' => config('planning.office'),
        ];
    }

    private function pivot(array $certificates): array
    {
        return collect($certificates)->mapWithKeys(fn ($c) => [$c['id'] => ['valid_until' => $c['valid_until'] ?: null]])->all();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'user_id' => ['nullable', 'exists:users,id'],
            'start_address' => ['required', 'string', 'max:255'],
            'start_latitude' => ['required', 'numeric', 'between:50,54'],
            'start_longitude' => ['required', 'numeric', 'between:3,8'],
            'work_start' => ['required', 'date_format:H:i'],
            'work_end' => ['required', 'date_format:H:i', 'after:work_start'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'active' => ['boolean'],
            'certificates' => ['array'],
            'certificates.*.id' => ['required', 'exists:certificates,id'],
            'certificates.*.valid_until' => ['nullable', 'date'],
        ], [
            'start_latitude.required' => 'Zoek eerst het startadres op.',
        ], [
            'name' => 'naam', 'start_address' => 'startadres', 'work_start' => 'begintijd', 'work_end' => 'eindtijd', 'color' => 'kleur',
        ]);
    }
}
