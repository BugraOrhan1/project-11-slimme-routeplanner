<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        Certificate::create($this->validated($request));

        return back()->with('success', 'Certificaat is toegevoegd.');
    }

    public function update(Request $request, Certificate $certificate): RedirectResponse
    {
        $certificate->update($this->validated($request));

        return back()->with('success', 'Certificaat is opgeslagen.');
    }

    public function destroy(Certificate $certificate): RedirectResponse
    {
        $certificate->delete();

        return back()->with('success', 'Certificaat is verwijderd.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ], [], ['name' => 'naam', 'description' => 'omschrijving']);
    }
}
