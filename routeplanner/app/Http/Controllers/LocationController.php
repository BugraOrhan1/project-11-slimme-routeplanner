<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Location;
use App\Models\Tank;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * US02 – Locaties (tankstations) met hun tanks beheren.
 */
class LocationController extends Controller
{
    public function create(Customer $customer): Response
    {
        return Inertia::render('Locations/Form', [
            'customer' => $customer->only('id', 'name'),
            'location' => null,
            'products' => Tank::PRODUCTS,
        ]);
    }

    public function store(Request $request, Customer $customer): RedirectResponse
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($customer, $data) {
            $location = $customer->locations()->create($data);
            $location->tanks()->createMany($data['tanks']);
        });

        return redirect()->route('customers.show', $customer)->with('success', "Locatie {$data['name']} is toegevoegd.");
    }

    public function edit(Location $location): Response
    {
        $location->load('customer', 'tanks');

        return Inertia::render('Locations/Form', [
            'customer' => $location->customer->only('id', 'name'),
            'location' => [
                ...$location->only('id', 'name', 'address', 'postal_code', 'city', 'region', 'latitude', 'longitude', 'access_notes'),
                'open_from' => $location->open_from ? substr($location->open_from, 0, 5) : null,
                'open_until' => $location->open_until ? substr($location->open_until, 0, 5) : null,
                'tanks' => $location->tanks->map->only('product', 'kind', 'capacity_liters', 'last_inspection'),
            ],
            'products' => Tank::PRODUCTS,
        ]);
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($location, $data) {
            $location->update($data);
            $location->tanks()->delete();
            $location->tanks()->createMany($data['tanks']);
        });

        return redirect()->route('customers.show', $location->customer_id)->with('success', 'Locatie is opgeslagen.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        $location->delete();

        return redirect()->route('customers.show', $location->customer_id)->with('success', "Locatie {$location->name} is verwijderd.");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'city' => ['required', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:50,54'],
            'longitude' => ['required', 'numeric', 'between:3,8'],
            'open_from' => ['nullable', 'date_format:H:i'],
            'open_until' => ['nullable', 'date_format:H:i', 'after:open_from'],
            'access_notes' => ['nullable', 'string', 'max:2000'],
            'tanks' => ['array'],
            'tanks.*.product' => ['required', 'string', 'max:50'],
            'tanks.*.kind' => ['required', 'in:ondergronds,bovengronds'],
            'tanks.*.capacity_liters' => ['nullable', 'integer', 'min:0'],
            'tanks.*.last_inspection' => ['nullable', 'date'],
        ], [
            'latitude.required' => 'Zoek eerst het adres op, zodat de locatie op de kaart staat.',
        ], [
            'name' => 'naam', 'address' => 'adres', 'city' => 'plaats', 'open_until' => 'open tot', 'open_from' => 'open vanaf',
            'tanks.*.product' => 'product',
        ]);
    }
}
