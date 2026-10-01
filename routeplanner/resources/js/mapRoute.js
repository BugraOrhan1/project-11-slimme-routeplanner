// Zet een route uit de backend om naar het formaat van <RouteMap>.
export function toMapRoute(route) {
    return {
        id: route.id,
        color: route.inspector.color,
        name: route.inspector.name,
        start: route.inspector.start,
        stops: route.stops.map((s) => ({
            lat: s.assignment.location.lat,
            lng: s.assignment.location.lng,
            label: s.assignment.location.name,
            sub: `${s.arrival}–${s.departure} · ${s.assignment.location.city}`,
        })),
    };
}
