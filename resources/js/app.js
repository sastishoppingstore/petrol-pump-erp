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

    // Mobile sidebar toggle (button + backdrop dono par attribute hai,
    // is liye sirf pehla element nahi, tamam matching elements bind karo).
    document.querySelectorAll('[data-sidebar-toggle]').forEach((el) => {
        el.addEventListener('click', () => {
            document.documentElement.classList.toggle('sidebar-open');
        });
    });

    /* ------------------------------------------------------------------
       Bootstrap-data-API shim (bina Bootstrap JS ke)
       Purani screens data-bs-toggle="modal" / "tab" istemal karti hain.
       Ye chhota sa shim un attributes ko naye CSS layer (.modal.show,
       .tab-pane.active) se jod deta hai taake har purana modal/tab
       dobara kaam kare — koi button dead na rahe.
       ------------------------------------------------------------------ */
    const closeModal = (modal) => {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    };

    document.addEventListener('click', (event) => {
        const modalToggle = event.target.closest('[data-bs-toggle="modal"]');
        if (modalToggle) {
            const target = document.querySelector(modalToggle.dataset.bsTarget || '');
            if (target) {
                target.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
            return;
        }

        const dismiss = event.target.closest('[data-bs-dismiss="modal"]');
        if (dismiss) {
            const modal = dismiss.closest('.modal');
            if (modal) closeModal(modal);
            return;
        }

        if (event.target.classList && event.target.classList.contains('modal')) {
            closeModal(event.target);
            return;
        }

        const tabToggle = event.target.closest('[data-bs-toggle="tab"], [data-bs-toggle="pill"]');
        if (tabToggle) {
            const tabList = tabToggle.closest('[role="tablist"], .nav');
            if (tabList) {
                tabList.querySelectorAll('[data-bs-toggle="tab"], [data-bs-toggle="pill"]')
                    .forEach((btn) => btn.classList.remove('active'));
            }
            tabToggle.classList.add('active');

            const pane = document.querySelector(tabToggle.dataset.bsTarget || '');
            if (pane) {
                const container = pane.closest('.tab-content') || pane.parentElement;
                if (container) {
                    container.querySelectorAll('.tab-pane')
                        .forEach((p) => p.classList.remove('active', 'show'));
                }
                pane.classList.add('active', 'show');
            }
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            document.querySelectorAll('.modal.show').forEach(closeModal);
        }
    });
});
