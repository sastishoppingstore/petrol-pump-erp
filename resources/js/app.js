// Petrol Pump ERP — global JS bundle.
// Vanilla ES6 + Alpine.js for lightweight interaction, Livewire for components,
// Chart.js for charts. No framework build, no SPA router.

import Alpine from 'alpinejs';
import {
    Chart,
    BarController,
    BarElement,
    CategoryScale,
    DoughnutController,
    ArcElement,
    LineController,
    LineElement,
    LinearScale,
    PointElement,
    Legend,
    Tooltip,
    Filler,
} from 'chart.js';

Chart.register(
    BarController, BarElement, CategoryScale,
    DoughnutController, ArcElement,
    LineController, LineElement, PointElement,
    LinearScale, Legend, Tooltip, Filler
);

// Shared chart defaults so every chart in the ERP looks the same.
Chart.defaults.font.family = "Inter, system-ui, -apple-system, 'Segoe UI', sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.maintainAspectRatio = false;

window.Chart = Chart;
window.Alpine = Alpine;
Alpine.start();

// Auto-dismiss flash toasts after a delay.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-auto-dismiss]').forEach((el) => {
        setTimeout(() => el.remove(), el.dataset.autoDismiss || 6000);
    });

    // Mobile sidebar toggle.
    const toggle = document.querySelector('[data-sidebar-toggle]');
    if (toggle) {
        toggle.addEventListener('click', () => {
            document.documentElement.classList.toggle('sidebar-open');
        });
    }
});
