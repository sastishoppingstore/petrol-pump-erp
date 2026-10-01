/* =====================================================================
   Petrol Pump ERP — Cinematic Motion Kit (dashboard-motion.js)
   GSAP + Chart.js presets. Sirf animation layer hai: data hamesha
   Blade se data-chart='@json(...)' attributes ke zariye aata hai.
   Koi hardcoded number nahi. Backend 100% Laravel/PHP rehta hai.
   ===================================================================== */
import gsap from 'gsap';

const REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const DESKTOP = window.matchMedia('(min-width: 1024px)').matches;
const FINE_POINTER = window.matchMedia('(pointer: fine)').matches;

window.gsap = gsap;

/* ---------- Count-up numbers --------------------------------------
   Blade: <span data-countup="12345" data-prefix="₨ " data-decimals="0">
------------------------------------------------------------------- */
export function initCountUps(scope = document) {
    const fmt = (v, d) => v.toLocaleString('en-PK', { minimumFractionDigits: d, maximumFractionDigits: d });
    scope.querySelectorAll('[data-countup]').forEach((el) => {
        const target = parseFloat(el.dataset.countup) || 0;
        const decimals = parseInt(el.dataset.decimals || '0', 10);
        const prefix = el.dataset.prefix || '';
        const suffix = el.dataset.suffix || '';
        if (REDUCED) { el.textContent = prefix + fmt(target, decimals) + suffix; return; }
        const state = { v: 0 };
        gsap.to(state, {
            v: target, duration: DESKTOP ? 2 : 1.1, ease: 'power2.out',
            onUpdate: () => { el.textContent = prefix + fmt(state.v, decimals) + suffix; },
            onComplete: () => { el.textContent = prefix + fmt(target, decimals) + suffix; },
        });
    });
}

/* ---------- 3D mouse tilt (desktop, fine pointer only) ------------ */
export function initTilt(scope = document) {
    if (!FINE_POINTER || REDUCED) return;
    scope.querySelectorAll('.tilt-3d').forEach((card) => {
        const rX = gsap.quickTo(card, 'rotationX', { duration: 0.5, ease: 'power3' });
        const rY = gsap.quickTo(card, 'rotationY', { duration: 0.5, ease: 'power3' });
        card.addEventListener('mousemove', (e) => {
            const r = card.getBoundingClientRect();
            rY(((e.clientX - r.left) / r.width - 0.5) * 12);
            rX(-((e.clientY - r.top) / r.height - 0.5) * 12);
        });
        card.addEventListener('mouseleave', () => { rX(0); rY(0); });
    });
}

/* ---------- Page entrance timeline -------------------------------- */
export function initEntrance(scope = document) {
    if (REDUCED) return;
    const tl = gsap.timeline({ defaults: { ease: 'power3.out' } });
    const pick = (sel) => scope.querySelectorAll(sel);
    if (pick('.page-head').length) tl.from('.page-head', { y: -26, opacity: 0, duration: 0.6 });
    if (pick('.stat-tile-3d').length) tl.from('.stat-tile-3d', { y: 44, opacity: 0, duration: 0.7, stagger: DESKTOP ? 0.09 : 0.05 }, '-=0.3');
    if (pick('.chart-card-3d').length) tl.from('.chart-card-3d', { y: 40, opacity: 0, duration: 0.7, stagger: 0.12 }, '-=0.4');
    if (pick('.glass-card').length) tl.from('.glass-card', { y: 30, opacity: 0, duration: 0.6, stagger: 0.06, clearProps: 'transform,opacity' }, '-=0.45');
}

/* ---------- Chart.js shared look ---------------------------------- */
const AXIS = {
    ticks: { color: '#94a3b8' },
    grid: { color: 'rgba(148,163,184,.08)' },
};
const AXIS_X = { ticks: { color: '#94a3b8' }, grid: { display: false } };

function gradientFor(chart, stops) {
    const { ctx, chartArea } = chart;
    if (!chartArea) return stops[stops.length - 1][1];
    const g = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
    stops.forEach(([o, c]) => g.addColorStop(o, c));
    return g;
}

/* ---------- Preset: progressive "film" line ------------------------
   data-chart-type="film-line" data-chart='{"labels":[...],"datasets":[...]}'
------------------------------------------------------------------- */
function filmLine(el, payload) {
    const totalDuration = REDUCED ? 0 : (DESKTOP ? 4500 : 1200);
    const n = Math.max(payload.labels.length, 2);
    const delayBetween = totalDuration / n;
    const previousY = (c) => c.index === 0
        ? c.chart.scales.y.getPixelForValue(0)
        : c.chart.getDatasetMeta(c.datasetIndex).data[c.index - 1].getProps(['y'], true).y;
    const ds = (payload.datasets || []).map((d, i) => ({
        ...d,
        borderWidth: 3, tension: 0.42, fill: true, pointRadius: 0, pointHoverRadius: 6,
        borderColor: d.borderColor || ['#f43f5e', '#3b82f6', '#f59e0b'][i % 3],
        backgroundColor: (c) => gradientFor(c.chart, [[0, 'rgba(244,63,94,0)'], [1, 'rgba(244,63,94,.38)']]),
    }));
    return new window.Chart(el, {
        type: 'line',
        data: { labels: payload.labels, datasets: ds },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            animation: REDUCED ? false : {
                x: { type: 'number', easing: 'linear', duration: delayBetween, from: NaN,
                     delay(c) { if (c.type !== 'data' || c.xStarted) return 0; c.xStarted = true; return c.index * delayBetween; } },
                y: { type: 'number', easing: 'linear', duration: delayBetween, from: previousY,
                     delay(c) { if (c.type !== 'data' || c.yStarted) return 0; c.yStarted = true; return c.index * delayBetween; } },
            },
            plugins: { legend: { labels: { color: '#e2e8f0', usePointStyle: true } } },
            scales: { x: AXIS_X, y: { ...AXIS, beginAtZero: true } },
        },
    });
}

/* ---------- Preset: gradient staggered bars ---------------------- */
function gradientBars(el, payload) {
    const ds = (payload.datasets || []).map((d) => ({
        ...d,
        borderRadius: 10, borderSkipped: false, maxBarThickness: 46,
        backgroundColor: (c) => gradientFor(c.chart, [[0, 'rgba(245,158,11,.30)'], [1, 'rgba(244,63,94,.95)']]),
    }));
    return new window.Chart(el, {
        type: 'bar',
        data: { labels: payload.labels, datasets: ds },
        options: {
            responsive: true, maintainAspectRatio: false,
            animation: REDUCED ? false : { duration: 1500, easing: 'easeOutQuart', delay: (c) => c.dataIndex * 130 },
            plugins: { legend: { display: (payload.datasets || []).length > 1, labels: { color: '#e2e8f0', usePointStyle: true } } },
            scales: { x: AXIS_X, y: { ...AXIS, beginAtZero: true } },
        },
    });
}

/* ---------- Preset: rotate-only doughnut ------------------------- */
function donutRotate(el, payload) {
    const palette = ['#f43f5e', '#3b82f6', '#f59e0b', '#10b981', '#8b5cf6', '#06b6d4'];
    const ds = (payload.datasets || []).map((d) => ({
        ...d,
        backgroundColor: d.backgroundColor || palette,
        borderWidth: 0, hoverOffset: 14, spacing: 2,
    }));
    return new window.Chart(el, {
        type: 'doughnut',
        data: { labels: payload.labels, datasets: ds },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '70%',
            animation: REDUCED ? false : { animateRotate: true, animateScale: false, duration: 2000, easing: 'easeOutQuart' },
            plugins: { legend: { position: 'bottom', labels: { color: '#e2e8f0', usePointStyle: true, padding: 16 } } },
        },
    });
}

const PRESETS = { 'film-line': filmLine, 'gradient-bars': gradientBars, 'donut-rotate': donutRotate };

/* ---------- Auto-init: har canvas[data-chart] --------------------- */
export function initCinematicCharts(scope = document) {
    if (!window.Chart) return;
    scope.querySelectorAll('canvas[data-chart]').forEach((el) => {
        if (el.dataset.chartInit) return;
        el.dataset.chartInit = '1';
        try {
            const payload = JSON.parse(el.dataset.chart || '{}');
            const preset = PRESETS[el.dataset.chartType || 'film-line'] || filmLine;
            preset(el, payload);
        } catch (e) { /* kharab JSON par chart skip — page nahi tootegi */ }
    });
}

/* ---------- Three.js particle background (desktop only) ---------- */
export async function initParticles() {
    if (!DESKTOP || REDUCED) return;
    const canvas = document.getElementById('fx-particles');
    if (!canvas) return;
    try {
        const THREE = await import('three');
        const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: false, powerPreference: 'low-power' });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));
        renderer.setSize(window.innerWidth, window.innerHeight);
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 100);
        camera.position.z = 8;
        const N = 220, pos = new Float32Array(N * 3);
        for (let i = 0; i < N * 3; i++) pos[i] = (Math.random() - 0.5) * 16;
        const geo = new THREE.BufferGeometry();
        geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
        const pts = new THREE.Points(geo, new THREE.PointsMaterial({ size: 0.05, color: 0xf43f5e, transparent: true, opacity: 0.5 }));
        scene.add(pts);
        let running = true;
        document.addEventListener('visibilitychange', () => { running = !document.hidden; });
        (function tick() {
            requestAnimationFrame(tick);
            if (!running) return;
            pts.rotation.y += 0.0006; pts.rotation.x += 0.0002;
            renderer.render(scene, camera);
        })();
    } catch (e) { /* WebGL na ho to background khamoshi se skip */ }
}

/* ---------- Boot --------------------------------------------------- */
document.addEventListener('DOMContentLoaded', () => {
    initEntrance();
    initCountUps();
    initTilt();
    initCinematicCharts();
    initParticles();
});

/* Livewire pages ke liye dobara init ka hook */
document.addEventListener('livewire:navigated', () => {
    initCountUps(); initTilt(); initCinematicCharts();
});
