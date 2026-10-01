// US11 – adres omzetten naar coördinaten via de PDOK Locatieserver (gratis, officiële Nederlandse adressen).
// Gebeurt in de browser, zodat de server er geen internettoegang voor nodig heeft.
const URL = 'https://api.pdok.nl/bzk/locatieserver/search/v3_1/free';

export async function geocode(query) {
    const params = new URLSearchParams({
        q: query,
        rows: '1',
        fq: 'type:adres',
        fl: 'weergavenaam,centroide_ll,postcode,woonplaatsnaam,provincienaam',
    });

    let doc;
    try {
        const response = await fetch(`${URL}?${params}`);
        doc = (await response.json()).response?.docs?.[0];
    } catch {
        throw new Error('Adresservice (PDOK) is niet bereikbaar. Controleer de internetverbinding.');
    }

    const match = doc?.centroide_ll?.match(/POINT\(([\d.]+) ([\d.]+)\)/);
    if (!match) {
        throw new Error('Adres niet gevonden. Controleer straat, huisnummer en plaats.');
    }

    return {
        lat: Number(match[2]),
        lng: Number(match[1]),
        label: doc.weergavenaam,
        postalCode: doc.postcode ?? null,
        city: doc.woonplaatsnaam ?? null,
        province: doc.provincienaam ?? null,
    };
}
