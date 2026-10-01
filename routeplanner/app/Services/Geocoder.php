<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * US11 – Adres omzetten naar coördinaten via de PDOK Locatieserver
 * (gratis, officiële Nederlandse adressen). Gebruikt bij de CSV-import;
 * de formulieren zoeken het adres in de browser op (resources/js/geocode.js).
 */
class Geocoder
{
    private const URL = 'https://api.pdok.nl/bzk/locatieserver/search/v3_1/free';

    /**
     * @return array{lat: float, lng: float, label: string, postalCode: ?string, city: ?string, province: ?string}|null
     */
    public function lookup(string $query): ?array
    {
        try {
            $doc = Http::timeout(5)->get(self::URL, [
                'q' => $query,
                'rows' => 1,
                'fq' => 'type:adres',
                'fl' => 'weergavenaam,centroide_ll,postcode,woonplaatsnaam,provincienaam',
            ])->json('response.docs.0');
        } catch (Throwable $e) {
            throw new RuntimeException('adresservice PDOK is niet bereikbaar vanaf de server ('.class_basename($e).'). Zie README: "Adressen opzoeken bij importeren".', previous: $e);
        }

        if (! $doc || ! preg_match('/POINT\(([\d.]+) ([\d.]+)\)/', $doc['centroide_ll'] ?? '', $m)) {
            return null;
        }

        return [
            'lat' => (float) $m[2],
            'lng' => (float) $m[1],
            'label' => $doc['weergavenaam'],
            'postalCode' => $doc['postcode'] ?? null,
            'city' => $doc['woonplaatsnaam'] ?? null,
            'province' => $doc['provincienaam'] ?? null,
        ];
    }
}
