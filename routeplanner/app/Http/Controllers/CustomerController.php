<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * US02 – Klanten beheren (maatschappijen zoals Shell, BP en Total).
 */
class CustomerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Customers/Index', [
            'customers' => Customer::withCount(['locations', 'assignments as open_count' => fn ($q) => $q->where('status', 'open')])
                ->orderBy('name')->get(),
        ]);
    }

    public function show(Customer $customer): Response
    {
        $customer->load(['locations' => fn ($q) => $q->with('tanks')
            ->withCount(['assignments as open_count' => fn ($a) => $a->where('status', 'open')])->orderBy('city')]);

        return Inertia::render('Customers/Show', ['customer' => $customer]);
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = Customer::create($this->validated($request));

        return redirect()->route('customers.show', $customer)->with('success', "Klant {$customer->name} is toegevoegd.");
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $customer->update($this->validated($request));

        return back()->with('success', 'Klantgegevens zijn opgeslagen.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()->route('customers.index')->with('success', "Klant {$customer->name} is verwijderd.");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ], [], ['name' => 'naam', 'contact_person' => 'contactpersoon', 'phone' => 'telefoon', 'color' => 'kleur']);
    }
}
