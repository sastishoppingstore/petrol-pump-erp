/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{js,ts}',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Noto Nastaliq Urdu', 'Gulzar', 'system-ui', '-apple-system', 'Segoe UI', 'sans-serif'],
                urdu: ['Noto Nastaliq Urdu', 'Gulzar', 'Jameel Noori Nastaliq', 'Urdu Typesetting', 'system-ui', 'sans-serif'],
            },
            colors: {
                // Vital Petroleum official branding palette.
                // primary / darkred admin Settings se aate hain: partials/theme.blade.php
                // --brand-*-rgb variables set karta hai, taake admin panel se rang
                // badalne par poori site bina rebuild ke update ho jaye.
                vital: {
                    DEFAULT: 'rgb(var(--brand-primary-rgb, 215 25 32) / <alpha-value>)',
                    primary: 'rgb(var(--brand-primary-rgb, 215 25 32) / <alpha-value>)',
                    red: 'rgb(var(--brand-primary-rgb, 215 25 32) / <alpha-value>)',
                    darkred: 'rgb(var(--brand-dark-rgb, 163 15 21) / <alpha-value>)',
                    'dark-red': 'rgb(var(--brand-dark-rgb, 163 15 21) / <alpha-value>)',
                    white: '#FFFFFF',
                    lightgrey: '#F6F6F6',
                    'light-grey': '#F6F6F6',
                    darktext: '#1B1B1B',
                    text: '#1B1B1B',
                    50: '#fef2f2',
                    100: '#fee2e2',
                    200: '#fecaca',
                    300: '#fca5a5',
                    400: '#f87171',
                    500: 'rgb(var(--brand-primary-rgb, 215 25 32) / <alpha-value>)',
                    600: '#dc2626',
                    700: '#b91c1c',
                    800: 'rgb(var(--brand-dark-rgb, 163 15 21) / <alpha-value>)',
                    900: '#7f1d1d',
                },
                // Petrol-station palette: dark navy shell, amber/green accents.
                navy: {
                    50: '#f2f6fa', 100: '#e2eaf3', 200: '#c0d4e6', 300: '#8fb4d3',
                    400: '#578ebc', 500: '#356fa4', 600: '#255a8c', 700: '#1d4773',
                    800: '#0f2540', 900: '#091a2e', 950: '#06111f',
                },
                amberx: {
                    400: '#fbbf24', 500: '#f59f00', 600: '#d97706',
                },
            },
            boxShadow: {
                card: '0 1px 2px 0 rgb(9 26 46 / 0.06), 0 1px 3px 0 rgb(9 26 46 / 0.10)',
                // Deep, soft elevation used by the 3D card system (design brief spec).
                '3d': '0 20px 25px -5px rgb(0 0 0 / 0.10), 0 8px 10px -6px rgb(0 0 0 / 0.10)',
                '3d-lg': '0 25px 50px -12px rgb(0 0 0 / 0.28), 0 12px 20px -8px rgb(0 0 0 / 0.18)',
                // Floating action button: brand glow + hard tactile base edge.
                fab: '0 14px 28px -6px rgb(var(--brand-primary-rgb, 215 25 32) / 0.55), 0 4px 0 0 rgb(var(--brand-dark-rgb, 163 15 21)), inset 0 2px 2px rgb(255 255 255 / 0.35)',
                glow: '0 0 44px rgb(var(--brand-primary-rgb, 215 25 32) / 0.45)',
            },
        },
    },
    plugins: [],
};
