<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Certificate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * US03 – Activiteiten (werkzaamheden met vaste tijd) en US05 – certificaten.
 */
class ActivityController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Activities/Index', [
            'activities' => Activity::with('certificate:id,name')->orderBy('name')->get(),
            'certificates' => Certificate::withCount(['inspectors', 'activities'])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Activity::create($this->validated($request));

        return back()->with('success', 'Activiteit is toegevoegd.');
    }

    public function update(Request $request, Activity $activity): RedirectResponse
    {
        $activity->update($this->validated($request));

        return back()->with('success', 'Activiteit is opgeslagen.');
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        $activity->delete();

        return back()->with('success', 'Activiteit is verwijderd.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:600'],
            'per_tank' => ['boolean'],
            'certificate_id' => ['nullable', 'exists:certificates,id'],
        ], [], ['name' => 'naam', 'duration_minutes' => 'duur']);
    }
}
