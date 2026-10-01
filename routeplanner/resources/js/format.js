// Kleine opmaak-helpers voor Nederlandse datums en tijden.

export function formatDate(value, options = { day: 'numeric', month: 'short', year: 'numeric' }) {
    if (!value) return '–';
    return new Date(`${value.slice(0, 10)}T12:00:00`).toLocaleDateString('nl-NL', options);
}

export function formatLongDate(value) {
    return formatDate(value, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
}

export function formatMonth(value) {
    if (!value) return '–';
    return new Date(`${value.slice(0, 7)}-01T12:00:00`).toLocaleDateString('nl-NL', { month: 'long', year: 'numeric' });
}

export function formatMinutes(minutes) {
    const m = Math.round(minutes ?? 0);
    const h = Math.floor(m / 60);
    return h ? `${h}u ${String(m % 60).padStart(2, '0')}m` : `${m}m`;
}

export function daysLabel(days) {
    if (days === null || days === undefined) return '';
    if (days < 0) return `${-days} dag${days === -1 ? '' : 'en'} te laat`;
    if (days === 0) return 'vandaag';
    if (days === 1) return 'morgen';
    return `nog ${days} dagen`;
}

export function todayIso() {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

export function shiftDate(iso, days) {
    const d = new Date(`${iso}T12:00:00`);
    d.setDate(d.getDate() + days);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

/** Google Maps-navigatie (Marijn: Maps wordt het meest gebruikt). */
export function mapsUrl(lat, lng) {
    return `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}&travelmode=driving`;
}
