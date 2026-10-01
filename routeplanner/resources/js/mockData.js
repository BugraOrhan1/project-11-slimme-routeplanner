import { reactive } from 'vue';

const today = new Date().toISOString().slice(0, 10);
const inspectors = [
    { id: 1, name: 'Marijn Klink', color: '#003D52', email: 'marijn@klink.nl', phone: '06 1234 5678', active: true, startAddress: 'Utrecht', workStart: '07:30', workEnd: '16:30', certificates: [{ name: 'Tankinspectie', expired: false }], start: { lat: 52.09, lng: 5.12, address: 'Utrecht' } },
    { id: 2, name: 'Sander de Boer', color: '#C8102E', email: 'sander@klink.nl', phone: '06 2345 6789', active: true, startAddress: 'Amersfoort', workStart: '08:00', workEnd: '17:00', certificates: [{ name: 'Tankinspectie', expired: false }], start: { lat: 52.16, lng: 5.39, address: 'Amersfoort' } },
    { id: 3, name: 'Nina Janssen', color: '#E07A00', email: '', phone: '06 3456 7890', active: true, startAddress: 'Arnhem', workStart: '07:30', workEnd: '16:00', certificates: [{ name: 'ATEX', expired: false }], start: { lat: 51.98, lng: 5.91, address: 'Arnhem' } },
];
const customers = [
    { id: 1, name: 'Shell', color: '#E3261B', contact_person: 'Shell Nederland', email: 'planning@shell.nl', phone: '030 123 4567', locations_count: 2, open_count: 3, locations: [{ id: 1, name: 'Shell De Meern', address: 'Rijksstraatweg 12', city: 'De Meern', region: 'Utrecht', latitude: 52.08, longitude: 5.03, tanks: [{ id: 1, product: 'Euro 95' }, { id: 2, product: 'Diesel' }], open_count: 2, open_from: '06:00', open_until: '22:00' }, { id: 2, name: 'Shell Ede', address: 'A12 Oost 4', city: 'Ede', region: 'Gelderland', latitude: 52.03, longitude: 5.67, tanks: [{ id: 3, product: 'Euro 95' }], open_count: 1, open_from: '06:00', open_until: '22:00' }] },
    { id: 2, name: 'BP', color: '#1d6a88', contact_person: 'BP Nederland', email: 'contact@bp.nl', phone: '020 123 4567', locations_count: 1, open_count: 2, locations: [{ id: 3, name: 'BP Veenendaal', address: 'Rondweg-West 8', city: 'Veenendaal', region: 'Utrecht', latitude: 52.03, longitude: 5.55, tanks: [{ id: 4, product: 'Ultimate' }], open_count: 2, open_from: '07:00', open_until: '21:00' }] },
    { id: 3, name: 'TotalEnergies', color: '#0b526d', contact_person: 'TotalEnergies', email: '', phone: '', locations_count: 1, open_count: 1, locations: [{ id: 4, name: 'Total Arnhem Noord', address: 'Amsterdamseweg 100', city: 'Arnhem', region: 'Gelderland', latitude: 52.01, longitude: 5.88, tanks: [{ id: 5, product: 'Diesel' }], open_count: 1, open_from: '06:00', open_until: '23:00' }] },
];
const activities = [{ id: 1, name: 'Visuele inspectie', minutes: 30, duration_minutes: 30, perTank: false, certificate: { name: 'Tankinspectie' } }, { id: 2, name: 'Dampretour meten', minutes: 20, duration_minutes: 20, perTank: true, per_tank: true, certificate: { name: 'ATEX' } }, { id: 3, name: 'Lekdetectie', minutes: 25, duration_minutes: 25, perTank: false, certificate: { name: 'Tankinspectie' } }];
const certificates = [{ id: 1, name: 'Tankinspectie', description: 'Periodieke tankinspectie', inspectors_count: 2, activities_count: 2 }, { id: 2, name: 'ATEX', description: 'Explosieveiligheid', inspectors_count: 1, activities_count: 1 }];
const assignments = [1, 2, 3, 4, 5].map((id, index) => {
    const source = customers[index % customers.length].locations[0];
    return { id, status: index < 3 ? 'open' : 'ingepland', deadline: `2026-10-${String(5 + index * 4).padStart(2, '0')}`, planMonth: '2026-10', priority: index === 0 ? 1 : 2, minutes: 75, location: { ...source, tanks: source.tanks.length }, activities, route: index > 2 ? { id: 1, date: '01-10-2026', inspector: inspectors[0].name, status: 'goedgekeurd' } : null };
});
const routeStops = assignments.slice(0, 3).map((assignment, index) => ({ id: index + 1, position: index + 1, arrival: `${String(9 + index).padStart(2, '0')}:00`, departure: `${String(10 + index).padStart(2, '0')}:15`, finishedAt: index === 0 ? '10:15' : null, startedAt: index === 0 ? '09:00' : null, distanceKm: 18 + index * 4, driveMinutes: 25, lat: assignment.location.latitude, lng: assignment.location.longitude, assignment }));
const plan = { id: 1, date: today, status: 'voorstel', totalKm: 74, driveMinutes: 95, workMinutes: 225, endTime: '15:30', inspector: inspectors[0], stops: routeStops };
const page = reactive({ name: 'Dashboard', props: {} });
const paginated = (data) => ({ data, current_page: 1, last_page: 1, from: 1, to: data.length, total: data.length, links: [] });
const formCustomers = customers.map((customer) => ({ ...customer, locations: customer.locations.map((location) => ({ ...location, tanks: location.tanks.length })) }));
const formActivities = activities.map((activity) => ({ ...activity, certificate: activity.certificate?.name ?? null }));
const dashboard = { stats: { open: 3, ingepland: 2, uitgevoerd: 8, overdue: 1, proposals: 2 }, deadlines: assignments.slice(0, 3).map((a, i) => ({ ...a, daysLeft: i - 1 })), mapPoints: assignments.slice(0, 4).map((a, i) => ({ lat: a.location.latitude, lng: a.location.longitude, title: `${a.location.name} – ${a.location.city}`, deadline: a.deadline, daysLeft: i - 1 })), todayRoutes: [{ id: 1, inspector: inspectors[0].name, color: inspectors[0].color, stops: 3, done: 1, km: 74, endTime: '15:30', status: 'voorstel' }], availableInspectors: inspectors.slice(1), recentChanges: [{ id: 1, user: 'Planner', description: 'Routevoorstel aangemaakt', at: '10 minuten geleden' }] };

export const getPageProps = (path) => {
    if (path.startsWith('/opdrachten/nieuw') || path.match(/^\/opdrachten\/\d+\/bewerken/)) return { assignment: null, locationId: null, customers: formCustomers, activities: formActivities, statuses: ['open', 'ingepland', 'uitgevoerd'] };
    if (path.startsWith('/opdrachten/importeren')) return { activities };
    if (path.startsWith('/inspecteurs/nieuw') || path.match(/^\/inspecteurs\/\d+\/bewerken/)) return { inspector: null, certificates, users: [], office: {} };
    if (path.startsWith('/opdrachten')) return { assignments: paginated(assignments), filters: { search: '', status: '', customer: '', activity: '', region: '', month: '', sort: 'deadline' }, options: { statuses: ['open', 'ingepland', 'uitgevoerd'], customers, activities, regions: ['Utrecht', 'Gelderland'] } };
    if (path.startsWith('/klanten/') && !path.endsWith('/klanten')) return { customer: customers[0] };
    if (path.startsWith('/klanten')) return { customers };
    if (path.startsWith('/inspecteurs')) return { inspectors, certificates, users: [] };
    if (path.startsWith('/werkzaamheden')) return { activities, certificates };
    if (path.startsWith('/wijzigingen')) return { logs: paginated([{ id: 1, at: 'Vandaag 10:15', user: 'Planner', role: 'Planner', action: 'aangemaakt', description: 'Routevoorstel aangemaakt', changes: null }]), filters: { user: '', type: '' }, users: [{ id: 1, name: 'Planner' }], types: ['aangemaakt', 'gewijzigd', 'verwijderd'] };
    if (path.startsWith('/gebruikers')) return { users: [{ id: 1, name: 'Planner', email: 'planner@klink.nl', role: 'planner', roleLabel: 'Planner' }], roles: { planner: 'Planner', bedrijfsleider: 'Bedrijfsleider', administratie: 'Administratie' } };
    if (path.startsWith('/meldingen')) return { alerts: [{ id: 1, message: 'Deadline voor Shell De Meern verloopt binnenkort.', at: 'Vandaag 09:30', assignmentId: 1, read: false }], alertDays: 30 };
    if (path.startsWith('/planning/nieuw')) return { date: today, inspectors, candidates: assignments, candidateCount: assignments.length, proposals: [plan] };
    if (path.startsWith('/planning')) return { week: today, weekNumber: 40, days: [0, 1, 2, 3, 4].map((offset) => ({ date: today, label: ['ma 1 okt', 'di 2 okt', 'wo 3 okt', 'do 4 okt', 'vr 5 okt'][offset], isToday: offset === 0 })), inspectors, routes: [{ id: 1, date: today, inspectorId: 1, status: 'voorstel', km: 74, endTime: '15:30', stops: routeStops.map((s) => ({ arrival: s.arrival, name: s.assignment.location.name, color: '#E3261B', done: !!s.finishedAt })) }] };
    if (path.startsWith('/routes/')) return { plan, conflicts: [], siblings: [], addable: assignments.slice(3) };
    if (path.startsWith('/mijn-route')) return { date: today, hasInspector: true, plan, upcoming: [] };
    return dashboard;
};

export { page };