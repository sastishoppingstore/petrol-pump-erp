// Petrol Pump ERP — global JS bundle
// Vanilla ES6 modules: Bootstrap 5, Chart.js, DataTables (server-side via DataTables API).

import * as bootstrap from 'bootstrap';

// Expose Bootstrap for use in inline page scripts / Blade pages.
window.bootstrap = bootstrap;

// Chart.js
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
);

// Global Chart defaults so every chart in the app looks the same.
Chart.defaults.font.family =
    "system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = '#6c757d';
Chart.defaults.maintainAspectRatio = false;
Chart.defaults.plugins.legend.labels.usePointStyle = true;

window.Chart = Chart;

// DataTables + Bootstrap 5 styling integration.
import 'datatables.net-bs5';

// Sidebar: mobile collapse.
document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const toggle = document.querySelector('[data-erp-sidebar-toggle]');

    if (toggle) {
        toggle.addEventListener('click', () => {
            body.classList.toggle('erp-sidebar-open');
        });
    }

    // Close the mobile sidebar after navigating to another page.
    document.querySelectorAll('.erp-sidebar .erp-nav-link').forEach((link) => {
        link.addEventListener('click', () => {
            body.classList.remove('erp-sidebar-open');
        });
    });

    // Auto-dismiss flash toasts.
    document.querySelectorAll('.alert[data-bs-dismiss="alert"]').forEach((el) => {
        setTimeout(() => {
            bootstrap.Alert.getOrCreateInstance(el).close();
        }, 6000);
    });
});

// Dismiss the flash toast message queue (Laravel default).
const flashEl = document.getElementById('flash-message');
if (flashEl) {
    bootstrap.Toast.getOrCreateInstance(flashEl).show();
}
