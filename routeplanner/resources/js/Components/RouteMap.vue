<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import L from 'leaflet';

/**
 * US16 – Route op kaart tonen: genummerde stops, lijn per inspecteur, startadres.
 * Wegen worden opgehaald bij de gratis OSRM-demoserver (OpenStreetMap); lukt dat niet,
 * dan tekenen we rechte lijnen tussen de stops.
 */
const props = defineProps({
    routes: { type: Array, default: () => [] }, // [{ id, color, name, start: {lat,lng,address}, stops: [{ lat, lng, label, sub }] }]
    points: { type: Array, default: () => [] }, // losse markers: [{ lat, lng, title, sub, color }]
    height: { type: String, default: '520px' },
    roads: { type: Boolean, default: true },
    highlight: { type: [Number, String], default: null }, // route-id om naar in te zoomen
});

const el = ref(null);
let map;
let layer;
const roadCache = new Map();

function numberIcon(n, color) {
    return L.divIcon({
        className: '',
        html: `<div style="background:${color};color:#fff;width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font:700 12px 'Open Sans',sans-serif;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.35)">${n}</div>`,
        iconSize: [26, 26],
        iconAnchor: [13, 13],
    });
}

function homeIcon(color) {
    return L.divIcon({
        className: '',
        html: `<div style="background:#fff;color:${color};width:28px;height:28px;border-radius:6px;display:flex;align-items:center;justify-content:center;border:2px solid ${color};box-shadow:0 1px 4px rgba(0,0,0,.35)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10l9-7 9 7v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg></div>`,
        iconSize: [28, 28],
        iconAnchor: [14, 14],
    });
}

function dotIcon(color) {
    return L.divIcon({
        className: '',
        html: `<div style="background:${color};width:14px;height:14px;border-radius:50%;border:2px solid #fff;box-shadow:0 1px 3px rgba(0,0,0,.4)"></div>`,
        iconSize: [14, 14],
        iconAnchor: [7, 7],
    });
}

async function roadGeometry(coords) {
    const key = coords.map((c) => c.join(',')).join(';');
    if (roadCache.has(key)) return roadCache.get(key);
    try {
        const url = `https://router.project-osrm.org/route/v1/driving/${coords.map(([lat, lng]) => `${lng},${lat}`).join(';')}?overview=full&geometries=geojson`;
        const response = await fetch(url);
        const json = await response.json();
        const line = json.routes?.[0]?.geometry?.coordinates?.map(([lng, lat]) => [lat, lng]) ?? null;
        roadCache.set(key, line);
        return line;
    } catch {
        return null;
    }
}

const escape = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]);

async function draw() {
    if (!map) return;
    layer.clearLayers();
    const bounds = [];
    const focus = [];

    for (const p of props.points) {
        if (!p.lat) continue;
        L.marker([p.lat, p.lng], { icon: dotIcon(p.color ?? '#003D52') })
            .bindPopup(`<strong>${escape(p.title)}</strong>${p.sub ? `<br>${escape(p.sub)}` : ''}`)
            .addTo(layer);
        bounds.push([p.lat, p.lng]);
    }

    for (const route of props.routes) {
        const start = route.start;
        const coords = [[start.lat, start.lng], ...route.stops.map((s) => [s.lat, s.lng]), [start.lat, start.lng]];
        bounds.push(...coords);
        if (props.highlight !== null && route.id === props.highlight) focus.push(...coords);

        L.marker([start.lat, start.lng], { icon: homeIcon(route.color), zIndexOffset: -100 })
            .bindPopup(`<strong>${escape(route.name)}</strong><br>Start: ${escape(start.address)}`)
            .addTo(layer);

        route.stops.forEach((s, i) => {
            L.marker([s.lat, s.lng], { icon: numberIcon(i + 1, route.color) })
                .bindPopup(`<strong>${i + 1}. ${escape(s.label)}</strong>${s.sub ? `<br>${escape(s.sub)}` : ''}`)
                .addTo(layer);
        });

        const straight = L.polyline(coords, { color: route.color, weight: 3, opacity: 0.55, dashArray: '6 6' }).addTo(layer);
        if (props.roads && route.stops.length) {
            roadGeometry(coords).then((line) => {
                if (line && layer.hasLayer(straight)) {
                    layer.removeLayer(straight);
                    L.polyline(line, { color: route.color, weight: 4, opacity: 0.85 }).addTo(layer);
                }
            });
        }
    }

    const target = focus.length ? focus : bounds;
    if (target.length) map.fitBounds(target, { padding: [30, 30], maxZoom: 12 });
}

onMounted(() => {
    map = L.map(el.value, { scrollWheelZoom: true }).setView([51.95, 5.2], 8);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);
    layer = L.layerGroup().addTo(map);
    draw();
});

watch(() => [props.routes, props.points, props.highlight], draw, { deep: true });

onBeforeUnmount(() => map?.remove());
</script>

<template>
    <div ref="el" :style="{ height }" class="z-0 w-full overflow-hidden rounded-lg"></div>
</template>
