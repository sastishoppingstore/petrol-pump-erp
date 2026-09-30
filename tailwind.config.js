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
                // Vital Petroleum official branding palette
                vital: {
                    DEFAULT: '#D71920',
                    primary: '#D71920',
                    red: '#D71920',
                    darkred: '#A30F15',
                    'dark-red': '#A30F15',
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
                    500: '#D71920',
                    600: '#dc2626',
                    700: '#b91c1c',
                    800: '#A30F15',
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
            },
        },
    },
    plugins: [],
};
