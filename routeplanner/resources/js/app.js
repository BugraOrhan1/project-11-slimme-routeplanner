import '../css/app.css';
import 'leaflet/dist/leaflet.css';

import { computed, createApp, h, shallowRef } from 'vue';
import AppLayout from './Layouts/AppLayout.vue';
import { getPageProps, page } from './mockData';

const pages = import.meta.glob('./Pages/**/*.vue', { eager: true, import: 'default' });
const pageComponent = shallowRef(null);

const routePaths = {
    dashboard: '/',
    'my-route': '/mijn-route',
    'alerts.index': '/meldingen',
    'planning.index': '/planning',
    'planning.create': '/planning/nieuw',
    'planning.propose': '/planning/voorstel',
    'assignments.index': '/opdrachten',
    'assignments.create': '/opdrachten/nieuw',
    'assignments.edit': '/opdrachten/:id/bewerken',
    'assignments.import': '/opdrachten/importeren',
    'customers.index': '/klanten',
    'customers.show': '/klanten/:id',
    'customers.store': '/klanten',
    'locations.create': '/klanten/:id/locaties/nieuw',
    'locations.edit': '/locaties/:id/bewerken',
    'inspectors.index': '/inspecteurs',
    'inspectors.create': '/inspecteurs/nieuw',
    'inspectors.edit': '/inspecteurs/:id/bewerken',
    'activities.index': '/werkzaamheden',
    'changes.index': '/wijzigingen',
    'users.index': '/gebruikers',
    'routes.show': '/routes/:id',
};

window.route = (name, params = {}) => {
    if (!name) {
        return {
            current: (pattern) => (Array.isArray(pattern) ? pattern : [pattern]).some((value) => {
                const key = String(value).replace('.*', '');
                return Object.entries(routePaths).some(([routeName, path]) => routeName.startsWith(key) && (path === '/' ? window.location.pathname === '/' : window.location.pathname.startsWith(path.replace(/:\w+/g, ''))));
            }),
        };
    }
    let path = routePaths[name] ?? '/';
    const values = Array.isArray(params) ? params : typeof params === 'object' ? Object.values(params) : [params];
    values.forEach((value) => (path = path.replace(/:[^/]+/, String(value))));
    if (!Array.isArray(params)) {
        const query = Object.entries(params).filter(([key, value]) => !routePaths[name]?.includes(`:${key}`) && valueIsPresent(value)).map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(value)}`).join('&');
        if (query) path += `?${query}`;
    }
    return path;
};

function valueIsPresent(value) { return value !== undefined && value !== null && value !== ''; }

function componentFor(path) {
    if (path.startsWith('/planning/nieuw')) return pages['./Pages/Planning/Create.vue'];
    if (path.startsWith('/planning')) return pages['./Pages/Planning/Index.vue'];
    if (path.startsWith('/opdrachten/importeren')) return pages['./Pages/Assignments/Import.vue'];
    if (path.startsWith('/opdrachten/nieuw') || path.match(/^\/opdrachten\/\d+\/bewerken/)) return pages['./Pages/Assignments/Form.vue'];
    if (path.startsWith('/opdrachten')) return pages['./Pages/Assignments/Index.vue'];
    if (path.match(/^\/klanten\/\d+\/locaties\/nieuw/) || path.match(/^\/locaties\/\d+\/bewerken/)) return pages['./Pages/Locations/Form.vue'];
    if (path.startsWith('/klanten/') && !path.endsWith('/klanten')) return pages['./Pages/Customers/Show.vue'];
    if (path.startsWith('/klanten')) return pages['./Pages/Customers/Index.vue'];
    if (path.startsWith('/inspecteurs/nieuw') || path.match(/^\/inspecteurs\/\d+\/bewerken/)) return pages['./Pages/Inspectors/Form.vue'];
    if (path.startsWith('/inspecteurs')) return pages['./Pages/Inspectors/Index.vue'];
    if (path.startsWith('/werkzaamheden')) return pages['./Pages/Activities/Index.vue'];
    if (path.startsWith('/wijzigingen')) return pages['./Pages/Changes/Index.vue'];
    if (path.startsWith('/gebruikers')) return pages['./Pages/Users/Index.vue'];
    if (path.startsWith('/meldingen')) return pages['./Pages/Alerts/Index.vue'];
    if (path.startsWith('/mijn-route')) return pages['./Pages/MyRoute.vue'];
    if (path.startsWith('/routes/')) return pages['./Pages/Routes/Show.vue'];
    return pages['./Pages/Dashboard.vue'];
}

function renderPath() {
    pageComponent.value = componentFor(window.location.pathname);
    page.name = pageComponent.value?.name ?? 'Dashboard';
    page.props = { auth: { user: { name: 'Planner', roleLabel: 'Planner', isInspector: false, canManageUsers: true } }, flash: {}, unreadAlerts: 1, ...getPageProps(window.location.pathname) };
}

window.addEventListener('popstate', renderPath);
renderPath();

const app = createApp({
    setup() {
        const currentProps = computed(() => {
            const { auth, flash, unreadAlerts, ...props } = page.props;
            return props;
        });
        return () => h(AppLayout, null, {
            default: () => h(pageComponent.value, currentProps.value),
        });
    },
});

app.config.globalProperties.route = window.route;
app.config.globalProperties.$page = page;
app.mount('#app');
